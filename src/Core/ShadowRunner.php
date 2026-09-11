<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Evolvex\ShadowRuntime\Comparison\BehaviorComparator;
use Evolvex\ShadowRuntime\Contracts\Comparator;
use Evolvex\ShadowRuntime\Contracts\Normalizer;
use Evolvex\ShadowRuntime\Contracts\Sampler;
use Evolvex\ShadowRuntime\Enums\OutcomeStatus;
use Evolvex\ShadowRuntime\Exceptions\BlockedEffectException;
use Evolvex\ShadowRuntime\Exceptions\ShadowBudgetExceededException;
use Evolvex\ShadowRuntime\Normalization\DefaultNormalizer;
use Evolvex\ShadowRuntime\Sandbox\EffectRecorder;
use Evolvex\ShadowRuntime\Sandbox\SandboxPolicy;
use Evolvex\ShadowRuntime\Support\Fingerprinter;
use Evolvex\ShadowRuntime\Telemetry\TelemetryRecorder;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use Throwable;

final readonly class ShadowRunner
{
    public function __construct(
        private Container $container,
        private Sampler $sampler,
        private TelemetryRecorder $telemetry,
        private Fingerprinter $fingerprinter,
        private ExperimentStateRepository $experiments,
    ) {
    }

    public function run(ShadowDefinition $definition): mixed
    {
        if (! (bool) config('shadow-runtime.enabled', true) || ! $this->experiments->isRunning($definition->experiment)) {
            return ($definition->primary)();
        }

        $maxDepth = (int) config('shadow-runtime.runtime.max_depth', 1);
        if (ShadowExecutionContext::depth() >= $maxDepth) {
            return ($definition->primary)();
        }

        $sampled = $this->sampler->shouldSample(
            $definition->experiment,
            $definition->subject,
            $definition->samplePercentage,
            (string) config('shadow-runtime.sampling.salt', 'shadow-runtime'),
        );

        if (! $sampled) {
            return ($definition->primary)();
        }

        $runId = (string) Str::ulid();
        $budgets = array_replace((array) config('shadow-runtime.budgets', []), $definition->budgets);

        $primaryPolicy = new SandboxPolicy([
            'level' => 0,
            'database_writes' => 'allow',
            'http' => 'allow',
            'queue' => 'allow',
            'mail' => 'allow',
            'notifications' => 'allow',
            'events' => 'observe',
        ]);

        $candidatePolicy = new SandboxPolicy((array) config('shadow-runtime.sandbox', []));
        if ($definition->sandboxConfigurator !== null) {
            ($definition->sandboxConfigurator)($candidatePolicy);
        }

        $primary = $this->execute(
            $definition->primary,
            new ShadowExecutionContext(
                $runId,
                $definition->experiment,
                $definition->version,
                $primaryPolicy,
                new EffectRecorder((int) ($budgets['max_effects'] ?? 50), (int) ($budgets['max_queries'] ?? 25)),
                $definition->tags,
                'primary',
            ),
            $budgets,
            enforceBudgets: false,
        );

        $shouldCompareAfterPrimaryException = (bool) config('shadow-runtime.runtime.compare_primary_exceptions', true);
        $candidate = null;
        $comparison = null;
        $fingerprint = null;

        if ($primary->successful() || $shouldCompareAfterPrimaryException) {
            $candidate = $this->execute(
                $definition->candidate,
                new ShadowExecutionContext(
                    $runId,
                    $definition->experiment,
                    $definition->version,
                    $candidatePolicy,
                    new EffectRecorder((int) ($budgets['max_effects'] ?? 50), (int) ($budgets['max_queries'] ?? 25)),
                    $definition->tags,
                    'candidate',
                ),
                $budgets,
                enforceBudgets: true,
            );

            $normalizer = $this->resolveNormalizer($definition->normalizer);
            $normalizedPrimary = $primary->status === OutcomeStatus::Returned ? $primary->withValue($normalizer->normalize($primary->value)) : $primary;
            $normalizedCandidate = $candidate->status === OutcomeStatus::Returned ? $candidate->withValue($normalizer->normalize($candidate->value)) : $candidate;

            $comparator = $this->resolveComparator($definition->comparator);
            $comparison = $comparator->compare($normalizedPrimary, $normalizedCandidate);

            if (! $comparison->matches) {
                $fingerprint = $this->fingerprinter->make($definition->experiment, $comparison->type->value, $comparison->changes);
                if ($definition->differenceHandler !== null) {
                    try {
                        ($definition->differenceHandler)($comparison);
                    } catch (Throwable) {
                        // Difference handlers are observability hooks and must never affect production.
                    }
                }
            }
        }

        $run = new ShadowRun(
            id: $runId,
            experiment: $definition->experiment,
            version: $definition->version,
            subjectHash: $this->subjectHash($definition->subject),
            tags: $definition->tags,
            primary: $primary,
            candidate: $candidate,
            comparison: $comparison,
            differenceFingerprint: $fingerprint,
            sandboxLevel: $candidatePolicy->sandboxLevel()->value,
            createdAt: new \DateTimeImmutable(),
            sampled: true,
            correlationId: $this->correlationId(),
        );

        $this->telemetry->record($run);

        if ($primary->exception !== null) {
            throw $primary->exception;
        }

        return $primary->value;
    }

    private function execute(callable $callback, ShadowExecutionContext $context, array $budgets, bool $enforceBudgets): Outcome
    {
        $start = hrtime(true);
        $memoryStart = memory_get_usage(true);
        $value = null;
        $exception = null;
        $status = OutcomeStatus::Returned;

        try {
            $value = ShadowExecutionContext::run($context, $callback);
        } catch (BlockedEffectException $e) {
            $exception = $e;
            $status = OutcomeStatus::BlockedEffect;
        } catch (ShadowBudgetExceededException $e) {
            $exception = $e;
            $status = OutcomeStatus::BudgetExceeded;
        } catch (Throwable $e) {
            $exception = $e;
            $status = OutcomeStatus::Threw;
        }

        $durationUs = (int) ((hrtime(true) - $start) / 1000);
        $memory = max(0, memory_get_usage(true) - $memoryStart);

        if ($enforceBudgets && $status === OutcomeStatus::Returned) {
            $timeoutMs = (int) ($budgets['timeout_ms'] ?? 0);
            $memoryMb = (int) ($budgets['memory_mb'] ?? 0);

            if ($timeoutMs > 0 && $durationUs > $timeoutMs * 1000) {
                $exception = new ShadowBudgetExceededException("Candidate exceeded {$timeoutMs}ms time budget. In-process mode detects this after execution; L3 isolation is required for hard cancellation.");
                $status = OutcomeStatus::TimedOut;
                $value = null;
            } elseif ($memoryMb > 0 && $memory > $memoryMb * 1024 * 1024) {
                $exception = new ShadowBudgetExceededException("Candidate exceeded {$memoryMb}MB memory budget.");
                $status = OutcomeStatus::BudgetExceeded;
                $value = null;
            }
        }

        $effects = $context->recorder->effects();
        $queries = $context->recorder->queryCount();

        return $status === OutcomeStatus::Returned
            ? Outcome::returned($value, $durationUs, $memory, $effects, $queries)
            : Outcome::failed($status, $exception ?? new \RuntimeException('Unknown shadow execution failure.'), $durationUs, $memory, $effects, $queries);
    }

    private function resolveComparator(Comparator|string|null $comparator): Comparator
    {
        if ($comparator instanceof Comparator) {
            return $comparator;
        }

        if (is_string($comparator)) {
            $resolved = $this->container->make($comparator);
            if (! $resolved instanceof Comparator) {
                throw new \InvalidArgumentException("{$comparator} must implement ".Comparator::class);
            }
            return $resolved;
        }

        return new BehaviorComparator();
    }

    private function resolveNormalizer(Normalizer|string|\Closure|null $normalizer): Normalizer
    {
        if ($normalizer instanceof Normalizer) {
            return $normalizer;
        }

        if (is_string($normalizer)) {
            $resolved = $this->container->make($normalizer);
            if (! $resolved instanceof Normalizer) {
                throw new \InvalidArgumentException("{$normalizer} must implement ".Normalizer::class);
            }
            return $resolved;
        }

        if ($normalizer instanceof \Closure) {
            return new class($normalizer) implements Normalizer {
                public function __construct(private readonly \Closure $callback) {}
                public function normalize(mixed $value): mixed { return ($this->callback)($value); }
            };
        }

        return new DefaultNormalizer();
    }

    private function subjectHash(mixed $subject): string
    {
        $value = is_scalar($subject) || $subject === null ? (string) $subject : serialize($subject);
        return hash_hmac('sha256', $value, (string) config('shadow-runtime.sampling.salt', 'shadow-runtime'));
    }

    private function correlationId(): ?string
    {
        try {
            if (class_exists(\Illuminate\Support\Facades\Context::class)) {
                $value = \Illuminate\Support\Facades\Context::get('correlation_id');
                return $value === null ? null : (string) $value;
            }
        } catch (Throwable) {
        }

        return null;
    }
}

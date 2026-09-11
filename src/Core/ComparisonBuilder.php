<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Closure;
use Evolvex\ShadowRuntime\Contracts\Comparator;
use Evolvex\ShadowRuntime\Contracts\Normalizer;
use Evolvex\ShadowRuntime\Sandbox\SandboxPolicy;

final class ComparisonBuilder
{
    private string $version;
    private mixed $subject = null;
    private float $samplePercentage;
    private array $tags = [];
    private ?Closure $primary = null;
    private ?Closure $candidate = null;
    private Comparator|string|null $comparator = null;
    private Normalizer|string|Closure|null $normalizer = null;
    private ?Closure $sandboxConfigurator = null;
    private array $budgets = [];
    private ?Closure $differenceHandler = null;

    public function __construct(
        private readonly ShadowRunner $runner,
        private readonly string $experiment,
        string $defaultVersion,
        float $defaultSamplePercentage,
    ) {
        $this->version = $defaultVersion;
        $this->samplePercentage = $defaultSamplePercentage;
    }

    public function version(string $version): self { $this->version = $version; return $this; }
    public function subject(mixed $subject): self { $this->subject = $subject; return $this; }
    public function sample(float $percentage): self { $this->samplePercentage = max(0, min(100, $percentage)); return $this; }
    public function tags(array $tags): self { $this->tags = $tags; return $this; }
    public function primary(callable $callback): self { $this->primary = Closure::fromCallable($callback); return $this; }
    public function candidate(callable $callback): self { $this->candidate = Closure::fromCallable($callback); return $this; }
    public function compareUsing(Comparator|string $comparator): self { $this->comparator = $comparator; return $this; }
    public function normalizeUsing(Normalizer|string|callable $normalizer): self
    {
        $this->normalizer = $normalizer instanceof Normalizer || is_string($normalizer) ? $normalizer : Closure::fromCallable($normalizer);
        return $this;
    }
    public function sandbox(callable $configure): self { $this->sandboxConfigurator = Closure::fromCallable($configure); return $this; }
    public function budget(?int $timeoutMs = null, ?int $memoryMb = null, ?int $maxQueries = null, ?int $maxEffects = null): self
    {
        $this->budgets = array_filter([
            'timeout_ms' => $timeoutMs,
            'memory_mb' => $memoryMb,
            'max_queries' => $maxQueries,
            'max_effects' => $maxEffects,
        ], static fn ($v) => $v !== null);
        return $this;
    }
    public function onDifference(callable $handler): self { $this->differenceHandler = Closure::fromCallable($handler); return $this; }

    public function run(): mixed
    {
        if ($this->primary === null || $this->candidate === null) {
            throw new \LogicException('Both primary() and candidate() must be configured before run().');
        }

        return $this->runner->run(new ShadowDefinition(
            experiment: $this->experiment,
            version: $this->version,
            subject: $this->subject,
            samplePercentage: $this->samplePercentage,
            tags: $this->tags,
            primary: $this->primary,
            candidate: $this->candidate,
            comparator: $this->comparator,
            normalizer: $this->normalizer,
            sandboxConfigurator: $this->sandboxConfigurator,
            budgets: $this->budgets,
            differenceHandler: $this->differenceHandler,
        ));
    }
}

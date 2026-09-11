<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Evolvex\ShadowRuntime\Contracts\ShadowOperation;
use Illuminate\Contracts\Container\Container;

final class OperationBuilder
{
    private mixed $input = null;
    private readonly ComparisonBuilder $comparison;

    /** @param class-string<ShadowOperation> $operationClass */
    public function __construct(Container $container, ShadowRunner $runner, string $operationClass, string $defaultVersion, float $defaultSample)
    {
        $operation = $container->make($operationClass);
        if (! $operation instanceof ShadowOperation) {
            throw new \InvalidArgumentException("{$operationClass} must implement ".ShadowOperation::class);
        }

        $name = method_exists($operation, 'name') ? (string) $operation->name() : $operationClass;
        $this->comparison = new ComparisonBuilder($runner, $name, $defaultVersion, $defaultSample);
        $this->comparison
            ->primary(fn () => $operation->primary($this->input))
            ->candidate(fn () => $operation->candidate($this->input));
    }

    public function input(mixed $input): self { $this->input = $input; return $this; }
    public function version(string $version): self { $this->comparison->version($version); return $this; }
    public function subject(mixed $subject): self { $this->comparison->subject($subject); return $this; }
    public function sample(float $percentage): self { $this->comparison->sample($percentage); return $this; }
    public function tags(array $tags): self { $this->comparison->tags($tags); return $this; }
    public function compareUsing($comparator): self { $this->comparison->compareUsing($comparator); return $this; }
    public function normalizeUsing($normalizer): self { $this->comparison->normalizeUsing($normalizer); return $this; }
    public function sandbox(callable $configure): self { $this->comparison->sandbox($configure); return $this; }
    public function budget(?int $timeoutMs = null, ?int $memoryMb = null, ?int $maxQueries = null, ?int $maxEffects = null): self
    { $this->comparison->budget($timeoutMs, $memoryMb, $maxQueries, $maxEffects); return $this; }
    public function onDifference(callable $handler): self { $this->comparison->onDifference($handler); return $this; }
    public function run(): mixed { return $this->comparison->run(); }
}

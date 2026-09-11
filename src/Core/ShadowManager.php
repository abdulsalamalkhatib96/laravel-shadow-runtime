<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Evolvex\ShadowRuntime\Contracts\ShadowOperation;
use Illuminate\Contracts\Container\Container;

final readonly class ShadowManager
{
    public function __construct(private Container $container, private ShadowRunner $runner)
    {
    }

    public function compare(string $experiment): ComparisonBuilder
    {
        return new ComparisonBuilder(
            $this->runner,
            $experiment,
            (string) config('shadow-runtime.default_version', 'dev'),
            (float) config('shadow-runtime.sampling.percentage', 1),
        );
    }

    /** @param class-string<ShadowOperation> $operationClass */
    public function operation(string $operationClass): OperationBuilder
    {
        return new OperationBuilder(
            $this->container,
            $this->runner,
            $operationClass,
            (string) config('shadow-runtime.default_version', 'dev'),
            (float) config('shadow-runtime.sampling.percentage', 1),
        );
    }
}

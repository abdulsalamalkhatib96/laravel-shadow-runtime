<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Evolvex\ShadowRuntime\Core\ComparisonBuilder compare(string $experiment)
 * @method static \Evolvex\ShadowRuntime\Core\OperationBuilder operation(string $operationClass)
 */
final class Shadow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shadow-runtime';
    }
}

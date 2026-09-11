<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Tests;

use Evolvex\ShadowRuntime\ShadowRuntimeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ShadowRuntimeServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('shadow-runtime.telemetry.exporter', 'null');
        $app['config']->set('shadow-runtime.sampling.percentage', 100);
    }
}

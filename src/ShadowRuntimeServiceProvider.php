<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime;

use Evolvex\ShadowRuntime\Commands\DiffsCommand;
use Evolvex\ShadowRuntime\Commands\DoctorCommand;
use Evolvex\ShadowRuntime\Commands\GateCommand;
use Evolvex\ShadowRuntime\Commands\InstallCommand;
use Evolvex\ShadowRuntime\Commands\ListCommand;
use Evolvex\ShadowRuntime\Commands\PauseCommand;
use Evolvex\ShadowRuntime\Commands\PurgeCommand;
use Evolvex\ShadowRuntime\Commands\ReportCommand;
use Evolvex\ShadowRuntime\Commands\ResumeCommand;
use Evolvex\ShadowRuntime\Commands\StatusCommand;
use Evolvex\ShadowRuntime\Contracts\Exporter;
use Evolvex\ShadowRuntime\Contracts\Sampler;
use Evolvex\ShadowRuntime\Core\ShadowManager;
use Evolvex\ShadowRuntime\Sampling\DeterministicSampler;
use Evolvex\ShadowRuntime\Sandbox\SandboxManager;
use Evolvex\ShadowRuntime\Support\Redactor;
use Evolvex\ShadowRuntime\Telemetry\Exporters\DatabaseExporter;
use Evolvex\ShadowRuntime\Telemetry\Exporters\LogExporter;
use Evolvex\ShadowRuntime\Telemetry\Exporters\NullExporter;
use Illuminate\Support\ServiceProvider;

final class ShadowRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shadow-runtime.php', 'shadow-runtime');

        $this->app->singleton(Sampler::class, DeterministicSampler::class);

        $this->app->singleton(Redactor::class, function (): Redactor {
            return new Redactor(
                (array) config('shadow-runtime.redaction.keys', []),
                (string) config('shadow-runtime.redaction.mask', '***'),
            );
        });

        $this->app->singleton(Exporter::class, function ($app): Exporter {
            return match ((string) config('shadow-runtime.telemetry.exporter', 'database')) {
                'database' => $app->make(DatabaseExporter::class),
                'log' => new LogExporter($app->make('log'), $app->make(Redactor::class)),
                'null' => new NullExporter(),
                default => $app->make((string) config('shadow-runtime.telemetry.exporter')),
            };
        });

        $this->app->singleton(ShadowManager::class);
        $this->app->alias(ShadowManager::class, 'shadow-runtime');
    }

    public function boot(SandboxManager $sandbox): void
    {
        $sandbox->install();

        $this->publishes([
            __DIR__.'/../config/shadow-runtime.php' => config_path('shadow-runtime.php'),
        ], 'shadow-runtime-config');

        $this->publishes([
            __DIR__.'/../database/migrations/2026_09_11_000001_create_shadow_runtime_tables.php'
                => database_path('migrations/'.date('Y_m_d_His').'_create_shadow_runtime_tables.php'),
        ], 'shadow-runtime-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ListCommand::class,
                StatusCommand::class,
                ReportCommand::class,
                DiffsCommand::class,
                PauseCommand::class,
                ResumeCommand::class,
                PurgeCommand::class,
                DoctorCommand::class,
                GateCommand::class,
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'shadow:install {--force : Overwrite published config}';
    protected $description = 'Publish Shadow Runtime configuration and migrations.';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'shadow-runtime-config',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->call('vendor:publish', [
            '--tag' => 'shadow-runtime-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->components->info('Shadow Runtime installed. Run php artisan migrate.');
        return self::SUCCESS;
    }
}

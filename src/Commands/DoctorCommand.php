<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Throwable;

final class DoctorCommand extends Command
{
    protected $signature = 'shadow:doctor';
    protected $description = 'Inspect Shadow Runtime safety configuration.';

    public function handle(DatabaseManager $db): int
    {
        $checks = [];
        $checks[] = ['Enabled', config('shadow-runtime.enabled') ? 'OK' : 'WARN', config('shadow-runtime.enabled') ? 'enabled' : 'disabled'];
        $level = (int) config('shadow-runtime.sandbox.level', 1);
        $checks[] = ['Safety level', $level >= 1 ? 'OK' : 'WARN', 'L'.$level];
        $checks[] = ['DB writes', config('shadow-runtime.sandbox.database_writes') === 'allow' ? 'WARN' : 'OK', (string) config('shadow-runtime.sandbox.database_writes')];
        $checks[] = ['HTTP', config('shadow-runtime.sandbox.http') === 'allow' ? 'WARN' : 'OK', (string) config('shadow-runtime.sandbox.http')];
        $checks[] = ['Queue', config('shadow-runtime.sandbox.queue') === 'allow' ? 'WARN' : 'OK', (string) config('shadow-runtime.sandbox.queue')];

        try {
            $db->table('shadow_runs')->limit(1)->count();
            $checks[] = ['Telemetry tables', 'OK', 'reachable'];
        } catch (Throwable $e) {
            $checks[] = ['Telemetry tables', 'FAIL', 'Run php artisan migrate'];
        }

        $checks[] = ['Raw PDO / raw cURL / external SDK', $level >= 3 ? 'OK' : 'WARN', $level >= 3 ? 'requires your L3 executor/network policy' : 'not guaranteed in-process'];
        $checks[] = ['Cache / filesystem writes', $level >= 3 ? 'OK' : 'WARN', $level >= 3 ? 'isolated by infrastructure adapter' : 'not transparently virtualized'];
        $checks[] = ['Hard timeout', $level >= 3 ? 'OK' : 'WARN', $level >= 3 ? 'provided by isolated executor' : 'inline mode only detects overrun after return'];

        $this->table(['Check','Status','Details'], $checks);
        return collect($checks)->contains(fn ($row) => $row[1] === 'FAIL') ? self::FAILURE : self::SUCCESS;
    }
}

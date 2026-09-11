<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class PurgeCommand extends Command
{
    protected $signature = 'shadow:purge {--all : Purge all run history}';
    protected $description = 'Purge expired payloads and old metrics.';

    public function handle(DatabaseManager $db): int
    {
        if ($this->option('all')) {
            $count = $db->table('shadow_runs')->delete();
            $this->components->info("Purged {$count} shadow runs.");
            return self::SUCCESS;
        }

        $payloads = $db->table('shadow_payloads')->whereNotNull('expires_at')->where('expires_at', '<', now())->delete();
        $days = (int) config('shadow-runtime.telemetry.metrics_retention_days', 90);
        $runs = $db->table('shadow_runs')->where('created_at', '<', now()->subDays($days))->delete();
        $this->components->info("Purged {$payloads} payloads and {$runs} old runs.");
        return self::SUCCESS;
    }
}

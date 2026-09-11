<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class ReportCommand extends Command
{
    protected $signature = 'shadow:report {experiment} {--limit=10}';
    protected $description = 'Show top differences for an experiment.';

    public function handle(DatabaseManager $db): int
    {
        $name = (string) $this->argument('experiment');
        $limit = max(1, (int) $this->option('limit'));
        $rows = $db->table('shadow_runs')
            ->where('experiment', $name)
            ->where('matched', false)
            ->selectRaw('difference_type, difference_fingerprint, COUNT(*) occurrences')
            ->groupBy('difference_type', 'difference_fingerprint')
            ->orderByDesc('occurrences')
            ->limit($limit)
            ->get();

        $this->table(['Type','Fingerprint','Occurrences'], $rows->map(fn ($r) => [
            $r->difference_type, $r->difference_fingerprint, $r->occurrences,
        ])->all());
        return self::SUCCESS;
    }
}

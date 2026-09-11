<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class StatusCommand extends Command
{
    protected $signature = 'shadow:status {experiment}';
    protected $description = 'Show status and aggregate metrics for an experiment.';

    public function handle(DatabaseManager $db): int
    {
        $name = (string) $this->argument('experiment');
        $experiment = $db->table('shadow_experiments')->where('name', $name)->first();
        if (! $experiment) {
            $this->components->error("Unknown experiment: {$name}");
            return self::FAILURE;
        }

        $metrics = $db->table('shadow_runs')->where('experiment', $name)
            ->selectRaw('COUNT(*) total')
            ->selectRaw('SUM(CASE WHEN matched = 1 THEN 1 ELSE 0 END) matches')
            ->selectRaw('SUM(CASE WHEN matched = 0 THEN 1 ELSE 0 END) differences')
            ->selectRaw('AVG(primary_duration_us) primary_avg_us')
            ->selectRaw('AVG(candidate_duration_us) candidate_avg_us')
            ->first();

        $total = (int) ($metrics->total ?? 0);
        $matches = (int) ($metrics->matches ?? 0);
        $agreement = $total > 0 ? round(($matches / $total) * 100, 4) : 0.0;

        $this->table(['Field','Value'], [
            ['Experiment', $name],
            ['Status', $experiment->status],
            ['Version', $experiment->version],
            ['Sandbox', 'L'.$experiment->sandbox_level],
            ['Samples', $total],
            ['Agreement', $agreement.'%'],
            ['Differences', (int) ($metrics->differences ?? 0)],
            ['Primary avg', round((float) ($metrics->primary_avg_us ?? 0) / 1000, 3).' ms'],
            ['Candidate avg', round((float) ($metrics->candidate_avg_us ?? 0) / 1000, 3).' ms'],
        ]);

        return self::SUCCESS;
    }
}

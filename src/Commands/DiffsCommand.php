<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class DiffsCommand extends Command
{
    protected $signature = 'shadow:diffs {experiment} {--limit=20}';
    protected $description = 'Show recent differing shadow runs.';

    public function handle(DatabaseManager $db): int
    {
        $rows = $db->table('shadow_runs')
            ->where('experiment', (string) $this->argument('experiment'))
            ->where('matched', false)
            ->latest('created_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get(['id','difference_type','difference_fingerprint','primary_status','candidate_status','created_at']);

        $this->table(['Run','Type','Fingerprint','Primary','Candidate','At'], $rows->map(fn ($r) => [
            $r->id, $r->difference_type, $r->difference_fingerprint, $r->primary_status, $r->candidate_status, $r->created_at,
        ])->all());
        return self::SUCCESS;
    }
}

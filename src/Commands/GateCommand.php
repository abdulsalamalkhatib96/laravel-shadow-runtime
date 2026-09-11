<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class GateCommand extends Command
{
    protected $signature = 'shadow:gate {experiment} {--agreement=99.9} {--max-candidate-error-rate=0.1} {--max-avg-latency-regression=10}';
    protected $description = 'Fail CI when shadow metrics do not meet promotion criteria.';

    public function handle(DatabaseManager $db): int
    {
        $name = (string) $this->argument('experiment');
        $m = $db->table('shadow_runs')->where('experiment', $name)
            ->selectRaw('COUNT(*) total')
            ->selectRaw('SUM(CASE WHEN matched = 1 THEN 1 ELSE 0 END) matches')
            ->selectRaw("SUM(CASE WHEN candidate_status NOT IN ('returned') THEN 1 ELSE 0 END) candidate_errors")
            ->selectRaw('AVG(primary_duration_us) p_avg')
            ->selectRaw('AVG(candidate_duration_us) c_avg')
            ->first();

        $total = (int) ($m->total ?? 0);
        if ($total === 0) {
            $this->components->error('No samples available.');
            return self::FAILURE;
        }

        $agreement = ((int) $m->matches / $total) * 100;
        $errorRate = ((int) $m->candidate_errors / $total) * 100;
        $p = max(1.0, (float) $m->p_avg);
        $latencyRegression = (((float) $m->c_avg - $p) / $p) * 100;

        $passed = $agreement >= (float) $this->option('agreement')
            && $errorRate <= (float) $this->option('max-candidate-error-rate')
            && $latencyRegression <= (float) $this->option('max-avg-latency-regression');

        $this->table(['Metric','Actual','Limit'], [
            ['Agreement', round($agreement, 4).'%', '>='.(float) $this->option('agreement').'%'],
            ['Candidate error rate', round($errorRate, 4).'%', '<='.(float) $this->option('max-candidate-error-rate').'%'],
            ['Avg latency regression', round($latencyRegression, 4).'%', '<='.(float) $this->option('max-avg-latency-regression').'%'],
        ]);

        $passed ? $this->components->info('Shadow gate PASSED.') : $this->components->error('Shadow gate FAILED.');
        return $passed ? self::SUCCESS : self::FAILURE;
    }
}

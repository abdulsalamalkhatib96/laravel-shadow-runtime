<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Evolvex\ShadowRuntime\Core\ExperimentStateRepository;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class ResumeCommand extends Command
{
    protected $signature = 'shadow:resume {experiment}';
    protected $description = 'Resume candidate execution for an experiment.';

    public function handle(DatabaseManager $db, ExperimentStateRepository $states): int
    {
        $name = (string) $this->argument('experiment');
        $query = $db->table('shadow_experiments')->where('name', $name);
        if (! $query->exists()) {
            $this->components->error("Unknown experiment: {$name}");
            return self::FAILURE;
        }
        $query->update(['status' => 'running', 'updated_at' => now()]);
        $states->forget($name);
        $this->components->info("Resumed {$name}.");
        return self::SUCCESS;
    }
}

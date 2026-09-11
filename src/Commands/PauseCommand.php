<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Evolvex\ShadowRuntime\Core\ExperimentStateRepository;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class PauseCommand extends Command
{
    protected $signature = 'shadow:pause {experiment}';
    protected $description = 'Pause candidate execution for an experiment.';

    public function handle(DatabaseManager $db, ExperimentStateRepository $states): int
    {
        $name = (string) $this->argument('experiment');
        $query = $db->table('shadow_experiments')->where('name', $name);
        if (! $query->exists()) {
            $this->components->error("Unknown experiment: {$name}");
            return self::FAILURE;
        }
        $query->update(['status' => 'paused', 'updated_at' => now()]);
        $states->forget($name);
        $this->components->info("Paused {$name}.");
        return self::SUCCESS;
    }
}

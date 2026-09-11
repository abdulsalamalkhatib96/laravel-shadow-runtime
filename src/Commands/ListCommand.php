<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

final class ListCommand extends Command
{
    protected $signature = 'shadow:list';
    protected $description = 'List known shadow experiments.';

    public function handle(DatabaseManager $db): int
    {
        $rows = $db->table('shadow_experiments')->orderBy('name')->get(['name','status','version','sample_percentage','sandbox_level','updated_at']);
        $this->table(['Name','Status','Version','Sample %','Safety','Updated'], $rows->map(fn ($r) => [
            $r->name, $r->status, $r->version, $r->sample_percentage, 'L'.$r->sandbox_level, $r->updated_at,
        ])->all());
        return self::SUCCESS;
    }
}

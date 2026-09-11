<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class ExperimentStateRepository
{
    public function __construct(private DatabaseManager $database, private CacheManager $cache)
    {
    }

    public function isRunning(string $experiment): bool
    {
        try {
            return (bool) $this->cache->remember(
                'shadow-runtime:experiment:'.hash('sha256', $experiment).':running',
                30,
                function () use ($experiment): bool {
                    $status = $this->database->table('shadow_experiments')->where('name', $experiment)->value('status');
                    return $status === null || $status === 'running';
                }
            );
        } catch (Throwable) {
            return true;
        }
    }

    public function forget(string $experiment): void
    {
        try {
            $this->cache->forget('shadow-runtime:experiment:'.hash('sha256', $experiment).':running');
        } catch (Throwable) {
        }
    }
}

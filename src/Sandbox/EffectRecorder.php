<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Sandbox;

use Evolvex\ShadowRuntime\Core\Effect;
use Evolvex\ShadowRuntime\Exceptions\ShadowBudgetExceededException;

final class EffectRecorder
{
    /** @var list<Effect> */
    private array $effects = [];
    private int $queryCount = 0;

    public function __construct(private readonly int $maxEffects = 50, private readonly int $maxQueries = 25)
    {
    }

    public function record(Effect $effect): void
    {
        if (count($this->effects) >= $this->maxEffects) {
            throw new ShadowBudgetExceededException("Shadow effect budget exceeded ({$this->maxEffects}).");
        }

        $this->effects[] = $effect;
    }

    public function query(): void
    {
        $this->queryCount++;

        if ($this->queryCount > $this->maxQueries) {
            throw new ShadowBudgetExceededException("Shadow query budget exceeded ({$this->maxQueries}).");
        }
    }

    /** @return list<Effect> */
    public function effects(): array
    {
        return $this->effects;
    }

    public function queryCount(): int
    {
        return $this->queryCount;
    }
}

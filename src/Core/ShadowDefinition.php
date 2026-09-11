<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Closure;
use Evolvex\ShadowRuntime\Contracts\Comparator;
use Evolvex\ShadowRuntime\Contracts\Normalizer;

final readonly class ShadowDefinition
{
    public function __construct(
        public string $experiment,
        public string $version,
        public mixed $subject,
        public float $samplePercentage,
        public array $tags,
        public Closure $primary,
        public Closure $candidate,
        public Comparator|string|null $comparator,
        public Normalizer|string|Closure|null $normalizer,
        public ?Closure $sandboxConfigurator,
        public array $budgets,
        public ?Closure $differenceHandler,
    ) {
    }
}

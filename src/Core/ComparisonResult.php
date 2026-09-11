<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Evolvex\ShadowRuntime\Enums\DifferenceType;

final readonly class ComparisonResult
{
    public function __construct(
        public bool $matches,
        public DifferenceType $type,
        public array $changes = [],
        public ?string $reason = null,
    ) {
    }

    public static function match(): self
    {
        return new self(true, DifferenceType::Match);
    }

    public static function difference(DifferenceType $type, array $changes, ?string $reason = null): self
    {
        return new self(false, $type, $changes, $reason);
    }
}

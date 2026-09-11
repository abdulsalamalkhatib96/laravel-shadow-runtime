<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use DateTimeImmutable;

final readonly class ShadowRun
{
    public function __construct(
        public string $id,
        public string $experiment,
        public string $version,
        public string $subjectHash,
        public array $tags,
        public Outcome $primary,
        public ?Outcome $candidate,
        public ?ComparisonResult $comparison,
        public ?string $differenceFingerprint,
        public int $sandboxLevel,
        public DateTimeImmutable $createdAt,
        public bool $sampled = true,
        public ?string $correlationId = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Evolvex\ShadowRuntime\Enums\OutcomeStatus;
use Throwable;

final readonly class Outcome
{
    public function __construct(
        public OutcomeStatus $status,
        public mixed $value,
        public ?Throwable $exception,
        public int $durationMicroseconds,
        public int $memoryBytes,
        public array $effects = [],
        public int $queryCount = 0,
    ) {
    }

    public static function returned(mixed $value, int $durationUs, int $memoryBytes, array $effects = [], int $queryCount = 0): self
    {
        return new self(OutcomeStatus::Returned, $value, null, $durationUs, $memoryBytes, $effects, $queryCount);
    }

    public static function failed(OutcomeStatus $status, Throwable $e, int $durationUs, int $memoryBytes, array $effects = [], int $queryCount = 0): self
    {
        return new self($status, null, $e, $durationUs, $memoryBytes, $effects, $queryCount);
    }

    public function successful(): bool
    {
        return $this->status === OutcomeStatus::Returned;
    }

    public function exceptionSnapshot(): ?array
    {
        if ($this->exception === null) {
            return null;
        }

        return [
            'class' => $this->exception::class,
            'message' => $this->exception->getMessage(),
            'code' => $this->exception->getCode(),
        ];
    }

    public function withValue(mixed $value): self
    {
        return new self(
            $this->status,
            $value,
            $this->exception,
            $this->durationMicroseconds,
            $this->memoryBytes,
            $this->effects,
            $this->queryCount,
        );
    }
}

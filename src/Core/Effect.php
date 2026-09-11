<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

final readonly class Effect
{
    public function __construct(
        public string $type,
        public string $operation,
        public ?string $resource = null,
        public array $payload = [],
        public bool $blocked = false,
        public bool $simulated = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'operation' => $this->operation,
            'resource' => $this->resource,
            'payload' => $this->payload,
            'blocked' => $this->blocked,
            'simulated' => $this->simulated,
        ];
    }
}

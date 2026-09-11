<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Exceptions;

use RuntimeException;

final class BlockedEffectException extends RuntimeException
{
    public function __construct(public readonly string $effectType, string $message)
    {
        parent::__construct($message);
    }
}

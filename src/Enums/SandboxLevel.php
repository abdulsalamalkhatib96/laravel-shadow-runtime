<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Enums;

enum SandboxLevel: int
{
    case Observe = 0;
    case Guarded = 1;
    case Restricted = 2;
    case Isolated = 3;

    public function label(): string
    {
        return match ($this) {
            self::Observe => 'L0 OBSERVE',
            self::Guarded => 'L1 GUARDED',
            self::Restricted => 'L2 RESTRICTED',
            self::Isolated => 'L3 ISOLATED',
        };
    }
}

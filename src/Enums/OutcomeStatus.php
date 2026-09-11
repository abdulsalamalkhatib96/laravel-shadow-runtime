<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Enums;

enum OutcomeStatus: string
{
    case Returned = 'returned';
    case Threw = 'threw';
    case TimedOut = 'timed_out';
    case BlockedEffect = 'blocked_effect';
    case BudgetExceeded = 'budget_exceeded';
    case Skipped = 'skipped';
}

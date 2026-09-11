<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Enums;

enum DifferenceType: string
{
    case Match = 'match';
    case Behavioral = 'behavioral_difference';
    case Exception = 'exception_difference';
    case SideEffect = 'side_effect_difference';
    case Performance = 'performance_regression';
    case CandidateFailure = 'candidate_failure';
    case Expected = 'expected_difference';
}

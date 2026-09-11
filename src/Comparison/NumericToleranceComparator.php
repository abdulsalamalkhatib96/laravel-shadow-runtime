<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Comparison;

use Evolvex\ShadowRuntime\Contracts\Comparator;
use Evolvex\ShadowRuntime\Core\ComparisonResult;
use Evolvex\ShadowRuntime\Core\Outcome;
use Evolvex\ShadowRuntime\Enums\DifferenceType;
use Evolvex\ShadowRuntime\Enums\OutcomeStatus;

final readonly class NumericToleranceComparator implements Comparator
{
    public function __construct(private float $tolerance = 0.000001)
    {
    }

    public function compare(Outcome $primary, Outcome $candidate): ComparisonResult
    {
        if ($primary->status !== OutcomeStatus::Returned || $candidate->status !== OutcomeStatus::Returned) {
            return (new StrictComparator())->compare($primary, $candidate);
        }

        if (! is_numeric($primary->value) || ! is_numeric($candidate->value)) {
            return ComparisonResult::difference(DifferenceType::Behavioral, ['reason' => 'non_numeric_value']);
        }

        $delta = abs((float) $primary->value - (float) $candidate->value);

        return $delta <= $this->tolerance
            ? ComparisonResult::match()
            : ComparisonResult::difference(DifferenceType::Behavioral, [
                'primary' => $primary->value,
                'candidate' => $candidate->value,
                'delta' => $delta,
                'tolerance' => $this->tolerance,
            ]);
    }
}

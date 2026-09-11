<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Comparison;

use Evolvex\ShadowRuntime\Contracts\Comparator;
use Evolvex\ShadowRuntime\Core\ComparisonResult;
use Evolvex\ShadowRuntime\Core\Outcome;
use Evolvex\ShadowRuntime\Enums\DifferenceType;
use Evolvex\ShadowRuntime\Enums\OutcomeStatus;

final class JsonComparator implements Comparator
{
    public function compare(Outcome $primary, Outcome $candidate): ComparisonResult
    {
        if ($primary->status !== OutcomeStatus::Returned || $candidate->status !== OutcomeStatus::Returned) {
            return (new StrictComparator())->compare($primary, $candidate);
        }

        $left = $this->canonicalize($primary->value);
        $right = $this->canonicalize($candidate->value);

        return $left === $right
            ? ComparisonResult::match()
            : ComparisonResult::difference(DifferenceType::Behavioral, ['value' => ['primary' => $left, 'candidate' => $right]]);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}

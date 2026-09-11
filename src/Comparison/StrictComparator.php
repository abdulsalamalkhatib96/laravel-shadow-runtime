<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Comparison;

use Evolvex\ShadowRuntime\Contracts\Comparator;
use Evolvex\ShadowRuntime\Core\ComparisonResult;
use Evolvex\ShadowRuntime\Core\Outcome;
use Evolvex\ShadowRuntime\Enums\DifferenceType;
use Evolvex\ShadowRuntime\Enums\OutcomeStatus;

final class StrictComparator implements Comparator
{
    public function compare(Outcome $primary, Outcome $candidate): ComparisonResult
    {
        if ($primary->status !== $candidate->status) {
            return ComparisonResult::difference(
                $candidate->status === OutcomeStatus::Returned ? DifferenceType::Exception : DifferenceType::CandidateFailure,
                ['status' => ['primary' => $primary->status->value, 'candidate' => $candidate->status->value]],
                'Outcome status differs.',
            );
        }

        if ($primary->status !== OutcomeStatus::Returned) {
            $left = $primary->exceptionSnapshot();
            $right = $candidate->exceptionSnapshot();

            return $left === $right
                ? ComparisonResult::match()
                : ComparisonResult::difference(DifferenceType::Exception, ['exception' => ['primary' => $left, 'candidate' => $right]]);
        }

        if ($primary->value !== $candidate->value) {
            return ComparisonResult::difference(
                DifferenceType::Behavioral,
                ['value' => ['primary' => $primary->value, 'candidate' => $candidate->value]],
                'Normalized return values differ.',
            );
        }

        $primaryEffects = array_map(static fn ($effect) => method_exists($effect, 'toArray') ? $effect->toArray() : $effect, $primary->effects);
        $candidateEffects = array_map(static fn ($effect) => method_exists($effect, 'toArray') ? $effect->toArray() : $effect, $candidate->effects);

        if ($primaryEffects !== $candidateEffects && $primaryEffects !== []) {
            return ComparisonResult::difference(
                DifferenceType::SideEffect,
                ['effects' => ['primary' => $primaryEffects, 'candidate' => $candidateEffects]],
                'Observed side-effect traces differ.',
            );
        }

        return ComparisonResult::match();
    }
}

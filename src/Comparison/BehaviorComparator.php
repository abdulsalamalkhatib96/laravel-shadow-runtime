<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Comparison;

use Evolvex\ShadowRuntime\Contracts\Comparator;
use Evolvex\ShadowRuntime\Core\ComparisonResult;
use Evolvex\ShadowRuntime\Core\Effect;
use Evolvex\ShadowRuntime\Core\Outcome;
use Evolvex\ShadowRuntime\Enums\DifferenceType;
use Evolvex\ShadowRuntime\Enums\OutcomeStatus;

/**
 * Compares normalized output first, then the observable effect signature.
 * blocked/simulated flags are deliberately ignored: they describe the sandbox,
 * not the intended business behavior.
 */
final class BehaviorComparator implements Comparator
{
    public function compare(Outcome $primary, Outcome $candidate): ComparisonResult
    {
        $valueComparison = (new StrictComparator())->compare(
            new Outcome($primary->status, $primary->value, $primary->exception, $primary->durationMicroseconds, $primary->memoryBytes),
            new Outcome($candidate->status, $candidate->value, $candidate->exception, $candidate->durationMicroseconds, $candidate->memoryBytes),
        );

        if (! $valueComparison->matches) {
            return $valueComparison;
        }

        $primaryEffects = $this->signature($primary->effects);
        $candidateEffects = $this->signature($candidate->effects);

        if ($primaryEffects !== $candidateEffects) {
            return ComparisonResult::difference(
                DifferenceType::SideEffect,
                ['effects' => ['primary' => $primaryEffects, 'candidate' => $candidateEffects]],
                'Observable effect signatures differ.',
            );
        }

        return ComparisonResult::match();
    }

    private function signature(array $effects): array
    {
        return array_map(static function (mixed $effect): array {
            if ($effect instanceof Effect) {
                return [
                    'type' => $effect->type,
                    'operation' => $effect->operation,
                    'resource' => $effect->resource,
                    'payload' => $effect->payload,
                ];
            }

            return (array) $effect;
        }, $effects);
    }
}

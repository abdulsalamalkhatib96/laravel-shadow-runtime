<?php

use Evolvex\ShadowRuntime\Facades\Shadow;

$decision = Shadow::compare('withdrawal-eligibility-v3')
    ->version('3.0.0')
    ->subject($snapshot->playerId)
    ->sample(5)
    ->primary(fn () => $legacyEligibility->decide($snapshot))
    ->candidate(fn () => $newEligibility->decide($snapshot))
    ->normalizeUsing(fn ($result) => [
        'allowed' => $result->allowed,
        'reason' => $result->reason,
    ])
    ->run();

<?php

use Evolvex\ShadowRuntime\Facades\Shadow;

$selected = Shadow::compare('sms-provider-selection-v2')
    ->version('2.0.0')
    ->subject($player->id)
    ->sample(10)
    ->tags(['site_id' => $site->id])
    ->primary(fn () => $oldResolver->resolve($context))
    ->candidate(fn () => $newResolver->resolve($context))
    ->sandbox(fn ($sandbox) => $sandbox
        ->captureDatabaseWrites()
        ->captureHttp()
        ->captureJobs())
    ->run();

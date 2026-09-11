<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Contracts;

interface Sampler
{
    public function shouldSample(string $experiment, mixed $subject, float $percentage, string $salt): bool;
}

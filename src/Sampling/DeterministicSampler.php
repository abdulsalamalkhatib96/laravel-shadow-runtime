<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Sampling;

use Evolvex\ShadowRuntime\Contracts\Sampler;

final class DeterministicSampler implements Sampler
{
    public function shouldSample(string $experiment, mixed $subject, float $percentage, string $salt): bool
    {
        if ($percentage <= 0.0) {
            return false;
        }

        if ($percentage >= 100.0) {
            return true;
        }

        $subjectKey = $this->subjectKey($subject);
        $hash = hash('sha256', $salt.'|'.$experiment.'|'.$subjectKey);
        $bucket = hexdec(substr($hash, 0, 8)) / 0xFFFFFFFF * 100;

        return $bucket < $percentage;
    }

    private function subjectKey(mixed $subject): string
    {
        if ($subject === null) {
            return 'anonymous';
        }

        if (is_scalar($subject) || $subject instanceof \Stringable) {
            return (string) $subject;
        }

        return hash('sha256', serialize($subject));
    }
}

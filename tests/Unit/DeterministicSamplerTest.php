<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Tests\Unit;

use Evolvex\ShadowRuntime\Sampling\DeterministicSampler;
use PHPUnit\Framework\TestCase;

final class DeterministicSamplerTest extends TestCase
{
    public function test_same_subject_always_gets_same_decision(): void
    {
        $sampler = new DeterministicSampler();
        $first = $sampler->shouldSample('exp', 42, 17.5, 'salt');

        for ($i = 0; $i < 100; $i++) {
            self::assertSame($first, $sampler->shouldSample('exp', 42, 17.5, 'salt'));
        }
    }

    public function test_zero_and_hundred_are_absolute(): void
    {
        $sampler = new DeterministicSampler();
        self::assertFalse($sampler->shouldSample('exp', 1, 0, 'salt'));
        self::assertTrue($sampler->shouldSample('exp', 1, 100, 'salt'));
    }
}

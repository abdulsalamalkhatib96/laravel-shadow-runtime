<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Tests\Unit;

use Evolvex\ShadowRuntime\Facades\Shadow;
use Evolvex\ShadowRuntime\Tests\TestCase;
use RuntimeException;

final class ShadowRuntimeTest extends TestCase
{
    public function test_returns_primary_value_even_when_candidate_differs(): void
    {
        $value = Shadow::compare('test')
            ->sample(100)
            ->primary(fn () => 'primary')
            ->candidate(fn () => 'candidate')
            ->run();

        self::assertSame('primary', $value);
    }

    public function test_primary_exception_is_rethrown(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('primary failed');

        Shadow::compare('test-exception')
            ->sample(100)
            ->primary(fn () => throw new RuntimeException('primary failed'))
            ->candidate(fn () => 'candidate')
            ->run();
    }

    public function test_nested_shadow_executes_only_inner_primary(): void
    {
        $value = Shadow::compare('outer')
            ->sample(100)
            ->primary(fn () => 'outer-primary')
            ->candidate(function () {
                return Shadow::compare('inner')
                    ->sample(100)
                    ->primary(fn () => 'inner-primary')
                    ->candidate(fn () => 'inner-candidate')
                    ->run();
            })
            ->run();

        self::assertSame('outer-primary', $value);
    }
}

<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Tests\Unit;

use Evolvex\ShadowRuntime\Support\Redactor;
use PHPUnit\Framework\TestCase;

final class RedactorTest extends TestCase
{
    public function test_redacts_nested_sensitive_keys(): void
    {
        $redactor = new Redactor(['password', 'api_key']);
        self::assertSame([
            'name' => 'A',
            'password' => '***',
            'nested' => ['my_api_key' => '***'],
        ], $redactor->redact([
            'name' => 'A',
            'password' => 'secret',
            'nested' => ['my_api_key' => '123'],
        ]));
    }
}

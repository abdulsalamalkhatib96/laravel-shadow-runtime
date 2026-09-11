<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Tests\Unit;

use Evolvex\ShadowRuntime\Support\SqlMutationDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SqlMutationDetectorTest extends TestCase
{
    #[DataProvider('queries')]
    public function test_detection(string $sql, bool $expected): void
    {
        self::assertSame($expected, (new SqlMutationDetector())->isMutation($sql));
    }

    public static function queries(): array
    {
        return [
            ['SELECT * FROM users', false],
            ['  -- hello'."\n".'SELECT * FROM users', false],
            ['UPDATE users SET name = ?', true],
            ['INSERT INTO users(name) VALUES (?)', true],
            ['DELETE FROM users WHERE id = 1', true],
            ['WITH x AS (SELECT 1) SELECT * FROM x', false],
            ['WITH x AS (SELECT 1) UPDATE users SET name = ? WHERE id = 1', true],
            ['/* comment */ TRUNCATE TABLE users', true],
        ];
    }
}

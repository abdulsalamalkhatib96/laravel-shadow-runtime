<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Core;

use Evolvex\ShadowRuntime\Sandbox\EffectRecorder;
use Evolvex\ShadowRuntime\Sandbox\SandboxPolicy;
use Illuminate\Support\Facades\Context;
use Throwable;

final class ShadowExecutionContext
{
    private const KEY = '__evolvex_shadow_runtime_stack';
    private static array $fallbackStack = [];

    public function __construct(
        public readonly string $runId,
        public readonly string $experiment,
        public readonly string $version,
        public readonly SandboxPolicy $policy,
        public readonly EffectRecorder $recorder,
        public readonly array $tags = [],
        public readonly string $role = 'candidate',
    ) {
    }

    public static function current(): ?self
    {
        try {
            if (class_exists(Context::class) && Context::getFacadeApplication() !== null) {
                $stack = Context::getHidden(self::KEY, []);
                return is_array($stack) && $stack !== [] ? end($stack) ?: null : null;
            }
        } catch (Throwable) {
            // Fallback for non-Laravel unit execution.
        }

        return self::$fallbackStack !== [] ? end(self::$fallbackStack) ?: null : null;
    }

    public static function depth(): int
    {
        try {
            if (class_exists(Context::class) && Context::getFacadeApplication() !== null) {
                $stack = Context::getHidden(self::KEY, []);
                return is_array($stack) ? count($stack) : 0;
            }
        } catch (Throwable) {
        }

        return count(self::$fallbackStack);
    }

    public static function run(self $context, callable $callback): mixed
    {
        $usingFacade = false;

        try {
            if (class_exists(Context::class) && Context::getFacadeApplication() !== null) {
                $stack = Context::getHidden(self::KEY, []);
                $stack = is_array($stack) ? $stack : [];
                $stack[] = $context;
                Context::addHidden(self::KEY, $stack);
                $usingFacade = true;
            } else {
                self::$fallbackStack[] = $context;
            }

            return $callback();
        } finally {
            if ($usingFacade) {
                $stack = Context::getHidden(self::KEY, []);
                if (is_array($stack)) {
                    array_pop($stack);
                    $stack === [] ? Context::forgetHidden(self::KEY) : Context::addHidden(self::KEY, $stack);
                }
            } else {
                array_pop(self::$fallbackStack);
            }
        }
    }
}

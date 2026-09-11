<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Support;

final class Redactor
{
    /** @param list<string> $keys */
    public function __construct(private readonly array $keys, private readonly string $mask = '***')
    {
    }

    public function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $redacted[$key] = $this->mask;
                continue;
            }

            $redacted[$key] = $this->redact($item);
        }

        return $redacted;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach ($this->keys as $sensitive) {
            if ($key === strtolower($sensitive) || str_contains($key, strtolower($sensitive))) {
                return true;
            }
        }

        return false;
    }
}

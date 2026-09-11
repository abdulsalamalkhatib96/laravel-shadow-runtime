<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Support;

final class Fingerprinter
{
    public function make(string $experiment, string $type, mixed $changes): string
    {
        $payload = json_encode([$experiment, $type, $this->stable($changes)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $payload ?: $experiment.'|'.$type);
    }

    private function stable(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->stable($item);
        }

        return $value;
    }
}

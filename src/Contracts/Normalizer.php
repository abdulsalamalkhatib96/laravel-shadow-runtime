<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Contracts;

interface Normalizer
{
    public function normalize(mixed $value): mixed;
}

<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Contracts;

interface ShadowOperation
{
    public function primary(mixed $input): mixed;

    public function candidate(mixed $input): mixed;
}

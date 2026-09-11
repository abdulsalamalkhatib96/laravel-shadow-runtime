<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Contracts;

use Evolvex\ShadowRuntime\Core\ComparisonResult;
use Evolvex\ShadowRuntime\Core\Outcome;

interface Comparator
{
    public function compare(Outcome $primary, Outcome $candidate): ComparisonResult;
}

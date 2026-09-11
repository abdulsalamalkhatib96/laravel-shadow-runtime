<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Contracts;

use Evolvex\ShadowRuntime\Core\ShadowRun;

interface Exporter
{
    public function export(ShadowRun $run): void;
}

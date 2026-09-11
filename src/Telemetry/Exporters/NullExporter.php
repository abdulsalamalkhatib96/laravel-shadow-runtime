<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Telemetry\Exporters;

use Evolvex\ShadowRuntime\Contracts\Exporter;
use Evolvex\ShadowRuntime\Core\ShadowRun;

final class NullExporter implements Exporter
{
    public function export(ShadowRun $run): void
    {
    }
}

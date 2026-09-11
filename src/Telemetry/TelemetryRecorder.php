<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Telemetry;

use Evolvex\ShadowRuntime\Contracts\Exporter;
use Evolvex\ShadowRuntime\Core\ShadowRun;
use Throwable;

final readonly class TelemetryRecorder
{
    public function __construct(private Exporter $exporter)
    {
    }

    public function record(ShadowRun $run): void
    {
        try {
            $this->exporter->export($run);
        } catch (Throwable $e) {
            // Shadow telemetry is fail-open by design. Do not let observability break production.
            try {
                logger()->warning('Shadow runtime telemetry export failed.', [
                    'experiment' => $run->experiment,
                    'run_id' => $run->id,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            } catch (Throwable) {
            }
        }
    }
}

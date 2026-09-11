<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Telemetry\Exporters;

use Evolvex\ShadowRuntime\Contracts\Exporter;
use Evolvex\ShadowRuntime\Core\ShadowRun;
use Evolvex\ShadowRuntime\Support\Redactor;
use Psr\Log\LoggerInterface;

final readonly class LogExporter implements Exporter
{
    public function __construct(private LoggerInterface $logger, private Redactor $redactor)
    {
    }

    public function export(ShadowRun $run): void
    {
        $this->logger->info('shadow_runtime.run', $this->redactor->redact([
            'run_id' => $run->id,
            'experiment' => $run->experiment,
            'version' => $run->version,
            'subject_hash' => $run->subjectHash,
            'matched' => $run->comparison?->matches,
            'difference_type' => $run->comparison?->type->value,
            'difference_fingerprint' => $run->differenceFingerprint,
            'primary_status' => $run->primary->status->value,
            'candidate_status' => $run->candidate?->status->value,
            'primary_duration_us' => $run->primary->durationMicroseconds,
            'candidate_duration_us' => $run->candidate?->durationMicroseconds,
            'sandbox_level' => $run->sandboxLevel,
            'tags' => $run->tags,
        ]));
    }
}

<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Telemetry\Exporters;

use Evolvex\ShadowRuntime\Contracts\Exporter;
use Evolvex\ShadowRuntime\Core\Effect;
use Evolvex\ShadowRuntime\Core\ShadowRun;
use Evolvex\ShadowRuntime\Support\Redactor;
use Illuminate\Database\DatabaseManager;

final readonly class DatabaseExporter implements Exporter
{
    public function __construct(private DatabaseManager $database, private Redactor $redactor)
    {
    }

    public function export(ShadowRun $run): void
    {
        $db = $this->database->connection();
        $comparison = $run->comparison;

        $db->table('shadow_experiments')->insertOrIgnore([
            'name' => $run->experiment,
            'status' => 'running',
            'version' => $run->version,
            'sample_percentage' => 1,
            'sandbox_level' => $run->sandboxLevel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $db->table('shadow_experiments')->where('name', $run->experiment)->update([
            'version' => $run->version,
            'sandbox_level' => $run->sandboxLevel,
            'updated_at' => now(),
        ]);

        $db->table('shadow_runs')->insert([
            'id' => $run->id,
            'experiment' => $run->experiment,
            'version' => $run->version,
            'subject_hash' => $run->subjectHash,
            'correlation_id' => $run->correlationId,
            'tags' => json_encode($this->redactor->redact($run->tags), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'primary_status' => $run->primary->status->value,
            'candidate_status' => $run->candidate?->status->value,
            'matched' => $comparison?->matches,
            'difference_type' => $comparison?->type->value,
            'difference_fingerprint' => $run->differenceFingerprint,
            'primary_duration_us' => $run->primary->durationMicroseconds,
            'candidate_duration_us' => $run->candidate?->durationMicroseconds,
            'primary_memory_bytes' => $run->primary->memoryBytes,
            'candidate_memory_bytes' => $run->candidate?->memoryBytes,
            'primary_query_count' => $run->primary->queryCount,
            'candidate_query_count' => $run->candidate?->queryCount,
            'sandbox_level' => $run->sandboxLevel,
            'created_at' => $run->createdAt->format('Y-m-d H:i:s.u'),
        ]);

        if ((bool) config('shadow-runtime.telemetry.store_payloads', false)) {
            $db->table('shadow_payloads')->insert([
                'run_id' => $run->id,
                'primary_payload' => $this->encodePayload($run->primary->successful() ? $run->primary->value : $run->primary->exceptionSnapshot()),
                'candidate_payload' => $this->encodePayload($run->candidate?->successful() ? $run->candidate?->value : $run->candidate?->exceptionSnapshot()),
                'diff_payload' => $this->encodePayload($comparison?->changes),
                'expires_at' => now()->addDays((int) config('shadow-runtime.telemetry.payload_retention_days', 3)),
                'created_at' => now(),
            ]);
        }

        if ((bool) config('shadow-runtime.telemetry.store_effects', true)) {
            $this->storeEffects($db, $run->id, 'primary', $run->primary->effects);
            $this->storeEffects($db, $run->id, 'candidate', $run->candidate?->effects ?? []);
        }
    }

    private function storeEffects($db, string $runId, string $role, array $effects): void
    {
        if ($effects === []) {
            return;
        }

        $rows = [];
        foreach ($effects as $effect) {
            if (! $effect instanceof Effect) {
                continue;
            }

            $rows[] = [
                'run_id' => $runId,
                'role' => $role,
                'type' => $effect->type,
                'operation' => $effect->operation,
                'resource' => $effect->resource,
                'blocked' => $effect->blocked,
                'simulated' => $effect->simulated,
                'payload' => $this->encodePayload($effect->payload),
                'created_at' => now(),
            ];
        }

        if ($rows !== []) {
            $db->table('shadow_effects')->insert($rows);
        }
    }

    private function encodePayload(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_encode($this->redactor->redact($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable) {
            return json_encode(['__unserializable' => get_debug_type($value)]);
        }
    }
}

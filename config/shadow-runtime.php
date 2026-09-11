<?php

declare(strict_types=1);

return [
    'enabled' => env('SHADOW_RUNTIME_ENABLED', true),

    'default_version' => env('SHADOW_RUNTIME_VERSION', 'dev'),

    'sampling' => [
        'percentage' => (float) env('SHADOW_RUNTIME_SAMPLE_PERCENT', 1),
        'salt' => env('SHADOW_RUNTIME_SAMPLING_SALT', env('APP_KEY', 'shadow-runtime')),
    ],

    'sandbox' => [
        // L0=observe, L1=guarded, L2=restricted, L3=isolated.
        'level' => (int) env('SHADOW_RUNTIME_SANDBOX_LEVEL', 1),
        'database_writes' => env('SHADOW_RUNTIME_DB_WRITES', 'block'), // allow|block|capture
        'http' => env('SHADOW_RUNTIME_HTTP', 'block'),                 // allow|block|capture
        'queue' => env('SHADOW_RUNTIME_QUEUE', 'block'),              // allow|block|capture
        'mail' => env('SHADOW_RUNTIME_MAIL', 'block'),                // allow|block|capture
        'notifications' => env('SHADOW_RUNTIME_NOTIFICATIONS', 'block'),
        'events' => env('SHADOW_RUNTIME_EVENTS', 'observe'),          // allow|observe
        'capture_event_payloads' => false,
    ],

    'budgets' => [
        'timeout_ms' => (int) env('SHADOW_RUNTIME_TIMEOUT_MS', 50),
        'memory_mb' => (int) env('SHADOW_RUNTIME_MEMORY_MB', 32),
        'max_queries' => (int) env('SHADOW_RUNTIME_MAX_QUERIES', 25),
        'max_effects' => (int) env('SHADOW_RUNTIME_MAX_EFFECTS', 50),
    ],

    'telemetry' => [
        'exporter' => env('SHADOW_RUNTIME_EXPORTER', 'database'), // database|log|null
        'store_payloads' => (bool) env('SHADOW_RUNTIME_STORE_PAYLOADS', false),
        'store_effects' => (bool) env('SHADOW_RUNTIME_STORE_EFFECTS', true),
        'payload_retention_days' => (int) env('SHADOW_RUNTIME_PAYLOAD_RETENTION_DAYS', 3),
        'metrics_retention_days' => (int) env('SHADOW_RUNTIME_METRICS_RETENTION_DAYS', 90),
    ],

    'redaction' => [
        'keys' => [
            'password', 'password_confirmation', 'token', 'access_token', 'refresh_token',
            'authorization', 'api_key', 'api_secret', 'secret', 'card_number', 'cvv',
        ],
        'mask' => '***',
    ],

    'runtime' => [
        'max_depth' => 1,
        'compare_primary_exceptions' => true,
        'fail_open' => true,
    ],
];

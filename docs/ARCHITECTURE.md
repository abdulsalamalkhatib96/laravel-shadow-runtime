# Architecture

## Request path

```text
request
  ├─ sampling decision
  ├─ primary (observed, allowed)
  ├─ candidate (guarded)
  ├─ normalize
  ├─ compare output + effects
  ├─ export telemetry (fail-open)
  └─ return/rethrow primary exactly
```

`ShadowExecutionContext` uses Laravel Context hidden data when available, with a fallback stack for non-Laravel execution. Permanent framework hooks consult the current context instead of globally swapping facades during every candidate call.

## Core components

- `ShadowManager`: public entry point.
- `ComparisonBuilder`: fluent experiment definition.
- `ShadowRunner`: lifecycle/orchestration.
- `DeterministicSampler`: stable subject sampling.
- `SandboxManager`: pre-effect guards and observers.
- `EffectRecorder`: effect/query budgets.
- `BehaviorComparator`: output + effect-signature comparison.
- `TelemetryRecorder`: fail-open exporter boundary.

## Database guard

Every Laravel DB connection receives a `beforeExecuting` callback. It counts queries and classifies mutating SQL. A candidate using `block`/`capture` is stopped before the mutating query runs.

The SQL classifier is a defensive guard, not a database permission system. L2 should use read-only database credentials as the enforcement boundary.

## Framework effect guards

- HTTP: `RequestSending` is captured before send and may be blocked.
- Queue: `JobQueueing` is captured before push and may be blocked.
- Mail: `MessageSending` is captured and may cancel delivery.
- Notification: `NotificationSending` is captured and may cancel channel delivery.
- Domain events: wildcard observation, excluding Illuminate and package events.

## Long-running workers

Hooks are installed once and become active only when a shadow context exists. No per-run facade swap is required. Laravel Context is preferred to reduce cross-request state leakage risk.

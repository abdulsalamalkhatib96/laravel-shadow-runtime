# Evolvex Shadow Runtime

Production dark-deployment and behavioral verification for Laravel business logic.

Run the existing implementation (`primary`) and a new implementation (`candidate`) on sampled production traffic. Only the primary result is returned to the application. The candidate is observed under a guarded sandbox and compared against the primary.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

Laravel 13 itself requires PHP 8.3+.

## Install

```bash
composer require evolvex/laravel-shadow-runtime
php artisan shadow:install
php artisan migrate
```

## Basic usage

```php
use Evolvex\ShadowRuntime\Facades\Shadow;

$provider = Shadow::compare('sms-provider-selection-v2')
    ->version('2.3.0')
    ->subject($player->id)
    ->sample(10)
    ->tags([
        'site_id' => $site->id,
        'country' => $player->country,
    ])
    ->primary(fn () => $oldResolver->resolve($context))
    ->candidate(fn () => $newResolver->resolve($context))
    ->sandbox(function ($sandbox) {
        $sandbox
            ->allowDatabaseReads()
            ->captureDatabaseWrites()
            ->captureHttp()
            ->captureJobs()
            ->captureEvents()
            ->captureMail()
            ->captureNotifications();
    })
    ->budget(timeoutMs: 50, memoryMb: 32, maxQueries: 20, maxEffects: 25)
    ->run();
```

`$provider` is always the primary result. Candidate failures, blocked effects, telemetry failures, and comparison differences do not replace the primary value.

## Named operations

```php
use Evolvex\ShadowRuntime\Contracts\ShadowOperation;

final class WithdrawalEligibilityV3 implements ShadowOperation
{
    public function primary(mixed $input): mixed
    {
        return app(LegacyWithdrawalEligibility::class)->decide($input);
    }

    public function candidate(mixed $input): mixed
    {
        return app(NewWithdrawalEligibility::class)->decide($input);
    }
}

$decision = Shadow::operation(WithdrawalEligibilityV3::class)
    ->input($snapshot)
    ->subject($snapshot->playerId)
    ->sample(25)
    ->run();
```

## What the guarded sandbox blocks

The package installs context-aware Laravel hooks. While a candidate is active it can block and record:

- mutating SQL executed through Laravel database connections, before execution;
- Laravel HTTP client requests, before network send;
- queue pushes through Laravel queue drivers, before enqueue;
- mail sending;
- Laravel notifications.

Application/domain events can be observed. Framework events are excluded from event traces to reduce noise.

The primary path is observed with an allow policy so its behavior is not changed.

## Safety levels

- **L0 OBSERVE**: comparison/observation only.
- **L1 GUARDED**: Laravel-level interception. This is the built-in runtime in this package.
- **L2 RESTRICTED**: combine L1 with read-only database credentials and isolated infrastructure namespaces.
- **L3 ISOLATED**: separate process/container, restricted network, resource limits, and isolated write targets.

### Critical limitation

L1 cannot guarantee containment of arbitrary PHP. Raw PDO connections created outside Laravel, raw `curl_*`, sockets, custom SDK transports, `exec()`, direct Redis clients, and direct filesystem access can bypass Laravel hooks.

Do not call L1 a hard security sandbox. For mutating or untrusted candidate code, use infrastructure isolation (L3).

Run:

```bash
php artisan shadow:doctor
```

before enabling a production experiment.

## Capture semantics

`captureDatabaseWrites()`, `captureHttp()`, and `captureJobs()` are **safe capture** policies: the effect is recorded and prevented. Because preventing an effect can change downstream candidate control flow, a candidate may finish with `blocked_effect`. This is intentional; the package never pretends that blocking a write is equivalent to full state virtualization.

For high-fidelity mutating workflows, refactor business decisions from effects or run the candidate in an isolated environment with realistic simulators/snapshots.

## Comparators

Default: `BehaviorComparator`.

It compares normalized return/exception behavior and observable effect signatures. Other built-ins:

```php
->compareUsing(Evolvex\ShadowRuntime\Comparison\StrictComparator::class)
->compareUsing(Evolvex\ShadowRuntime\Comparison\JsonComparator::class)
->compareUsing(new Evolvex\ShadowRuntime\Comparison\NumericToleranceComparator(0.001))
```

You can implement `Evolvex\ShadowRuntime\Contracts\Comparator` for domain equivalence.

## Normalization

The default normalizer handles scalars, arrays, collections, enums, dates and Eloquent models. For Eloquent it uses `getAttributes()` and already-loaded relations instead of blindly calling `toArray()`, avoiding accidental accessor/appended-relation work.

Custom:

```php
->normalizeUsing(fn ($decision) => [
    'allowed' => $decision->allowed,
    'reason' => $decision->reason,
])
```

## Deterministic sampling

Sampling is based on a stable hash of experiment + subject + salt. The same subject receives a stable decision for a fixed percentage/salt.

```php
->subject($player->id)
->sample(5)
```

## Failure model

Shadow Runtime is fail-open around telemetry and callbacks. Candidate errors never replace the primary result. If the primary throws, its original exception is rethrown after the shadow comparison attempt.

Nested shadow candidate execution is suppressed by default (`runtime.max_depth = 1`) to avoid combinatorial execution.

## Commands

```bash
php artisan shadow:list
php artisan shadow:status sms-provider-selection-v2
php artisan shadow:report sms-provider-selection-v2
php artisan shadow:diffs sms-provider-selection-v2
php artisan shadow:pause sms-provider-selection-v2
php artisan shadow:resume sms-provider-selection-v2
php artisan shadow:purge
php artisan shadow:doctor
php artisan shadow:gate sms-provider-selection-v2 \
  --agreement=99.9 \
  --max-candidate-error-rate=0.1 \
  --max-avg-latency-regression=10
```

`shadow:gate` exits non-zero when thresholds fail, so it can be used in CI/CD promotion gates.

## Telemetry

Database exporter tables:

- `shadow_experiments`
- `shadow_runs`
- `shadow_effects`
- `shadow_payloads`

Raw payload persistence is disabled by default. Effects are stored by default. Sensitive keys pass through a configurable redactor.

Available exporters:

```env
SHADOW_RUNTIME_EXPORTER=database
# log
# null
```

A custom exporter may implement `Evolvex\ShadowRuntime\Contracts\Exporter` and be configured by class name.

## Production recommendations

1. Start at 0.1-1% sampling.
2. Keep raw payload storage disabled unless necessary.
3. Use a read-only DB user for candidate infrastructure whenever possible.
4. Do not shadow an entire mutating payment workflow in-process. Shadow its decision layer or use L3 isolation.
5. Segment agreement by meaningful business dimensions instead of trusting only a global percentage.
6. Treat `blocked_effect` as useful evidence that the candidate crossed a production-effect boundary.
7. Run `shadow:doctor` after every infrastructure change.

See `docs/ARCHITECTURE.md` and `docs/SAFETY.md`.

# Safety model

The built-in runtime is a **guarded application sandbox**, not an OS security sandbox.

## Guaranteed only through supported Laravel paths

- Laravel database `Connection` queries are guarded before execution.
- Laravel HTTP client requests are observed before network send.
- Laravel queue pushes emit `JobQueueing` before enqueue.
- Laravel mail and notification sending can be canceled by pre-send events.

## Not transparently contained at L1

- raw PDO created outside Laravel's DB manager;
- direct curl/socket/network usage;
- third-party SDK transports that do not use Laravel HTTP;
- direct Redis clients;
- direct filesystem/S3 SDK writes;
- shell/process execution;
- native extensions performing I/O;
- externally committed effects performed before the candidate enters the runtime.

Use read-only credentials and network policy for L2/L3. For payment/wallet workflows, prefer shadowing pure decision objects using immutable input snapshots.

## Time budget

Inline PHP cannot be safely preempted in a framework-agnostic way. The inline runtime measures time and reports `timed_out` after the candidate returns. Hard cancellation requires a process/container executor.

<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Sandbox;

use Evolvex\ShadowRuntime\Core\Effect;
use Evolvex\ShadowRuntime\Core\ShadowExecutionContext;
use Evolvex\ShadowRuntime\Exceptions\BlockedEffectException;
use Evolvex\ShadowRuntime\Support\SqlMutationDetector;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Queue\Events\JobQueueing;
use Throwable;

final class SandboxManager
{
    /** @var array<int, true> */
    private array $guardedConnections = [];

    public function __construct(
        private readonly Dispatcher $events,
        private readonly DatabaseManager $database,
        private readonly SqlMutationDetector $sqlMutationDetector,
    ) {
    }

    public function install(): void
    {
        foreach ($this->database->getConnections() as $connection) {
            $this->guardConnection($connection);
        }

        $this->events->listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            $this->guardConnection($event->connection);
        });

        $this->events->listen(RequestSending::class, function (RequestSending $event): void {
            $context = ShadowExecutionContext::current();
            if ($context === null) {
                return;
            }

            $request = $event->request;
            $policy = $context->policy->policyFor('http');

            $context->recorder->record(new Effect(
                'http',
                strtoupper($request->method()),
                $request->url(),
                ['headers' => array_keys($request->headers())],
                blocked: $policy !== 'allow',
            ));

            if ($policy !== 'allow') {
                throw new BlockedEffectException('http', sprintf('Shadow HTTP request blocked: %s %s', strtoupper($request->method()), $request->url()));
            }
        });

        $this->events->listen(JobQueueing::class, function (JobQueueing $event): void {
            $context = ShadowExecutionContext::current();
            if ($context === null) {
                return;
            }

            $policy = $context->policy->policyFor('queue');
            $job = is_object($event->job) ? $event->job::class : (string) $event->job;

            $context->recorder->record(new Effect(
                'queue',
                'dispatch',
                $job,
                [
                    'connection' => $event->connectionName,
                    'queue' => $event->queue,
                    'delay' => $event->delay,
                ],
                blocked: $policy !== 'allow',
            ));

            if ($policy !== 'allow') {
                throw new BlockedEffectException('queue', "Shadow queue dispatch blocked: {$job}");
            }
        });

        $this->events->listen(MessageSending::class, function (MessageSending $event): mixed {
            $context = ShadowExecutionContext::current();
            if ($context === null) {
                return null;
            }

            $policy = $context->policy->policyFor('mail');
            $message = $event->message;

            $context->recorder->record(new Effect(
                'mail',
                'send',
                'mail',
                [
                    'to_count' => count($message->getTo()),
                    'cc_count' => count($message->getCc()),
                    'bcc_count' => count($message->getBcc()),
                ],
                blocked: $policy !== 'allow',
            ));

            // Laravel mailers honor false from MessageSending listeners and do not send.
            return $policy !== 'allow' ? false : null;
        });

        $this->events->listen(NotificationSending::class, function (NotificationSending $event): mixed {
            $context = ShadowExecutionContext::current();
            if ($context === null) {
                return null;
            }

            $policy = $context->policy->policyFor('notifications');

            $context->recorder->record(new Effect(
                'notification',
                'send',
                $event->notification::class,
                ['channel' => $event->channel],
                blocked: $policy !== 'allow',
            ));

            return $policy !== 'allow' ? false : null;
        });

        // Observe application/domain events. Framework events are intentionally excluded
        // to avoid polluting behavioral signatures with transport internals.
        $this->events->listen('*', function (string $eventName, array $payload): void {
            $context = ShadowExecutionContext::current();
            if ($context === null || $context->policy->policyFor('events') === 'allow') {
                return;
            }

            if (str_starts_with($eventName, 'Illuminate\\') || str_starts_with($eventName, 'Evolvex\\ShadowRuntime\\')) {
                return;
            }

            try {
                $context->recorder->record(new Effect(
                    'event',
                    'dispatch',
                    $eventName,
                    ['payload_count' => count($payload)],
                ));
            } catch (Throwable) {
                // Event observation must never interfere with the primary application flow.
            }
        });
    }

    private function guardConnection(Connection $connection): void
    {
        $id = spl_object_id($connection);
        if (isset($this->guardedConnections[$id])) {
            return;
        }

        $this->guardedConnections[$id] = true;

        $connection->beforeExecuting(function (string $query, array $bindings, Connection $connection): void {
            $context = ShadowExecutionContext::current();
            if ($context === null) {
                return;
            }

            $context->recorder->query();

            if (! $this->sqlMutationDetector->isMutation($query)) {
                return;
            }

            $policy = $context->policy->policyFor('database_writes');

            $context->recorder->record(new Effect(
                'database',
                'write',
                $connection->getName(),
                [
                    'sql' => $this->compactSql($query),
                    'bindings_count' => count($bindings),
                ],
                blocked: $policy !== 'allow',
            ));

            if ($policy !== 'allow') {
                throw new BlockedEffectException('database', 'Shadow database mutation blocked before execution.');
            }
        });
    }

    private function compactSql(string $sql): string
    {
        return mb_substr((string) preg_replace('/\s+/', ' ', trim($sql)), 0, 1000);
    }
}

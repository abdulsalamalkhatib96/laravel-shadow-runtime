<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Sandbox;

use Evolvex\ShadowRuntime\Enums\SandboxLevel;

final class SandboxPolicy
{
    private SandboxLevel $level;
    private array $effects;

    public function __construct(array $defaults = [])
    {
        $this->level = SandboxLevel::tryFrom((int) ($defaults['level'] ?? 1)) ?? SandboxLevel::Guarded;
        $this->effects = [
            'database_writes' => $defaults['database_writes'] ?? 'block',
            'http' => $defaults['http'] ?? 'block',
            'queue' => $defaults['queue'] ?? 'block',
            'mail' => $defaults['mail'] ?? 'block',
            'notifications' => $defaults['notifications'] ?? 'block',
            'events' => $defaults['events'] ?? 'observe',
        ];
    }

    public function level(SandboxLevel|int $level): self
    {
        $this->level = is_int($level) ? (SandboxLevel::tryFrom($level) ?? SandboxLevel::Guarded) : $level;

        return $this;
    }

    public function databaseReads(): self
    {
        return $this;
    }

    public function allowDatabaseReads(): self
    {
        return $this;
    }

    public function blockDatabaseWrites(): self
    {
        return $this->set('database_writes', 'block');
    }

    public function captureDatabaseWrites(): self
    {
        return $this->set('database_writes', 'capture');
    }

    public function allowDatabaseWrites(): self
    {
        return $this->set('database_writes', 'allow');
    }

    public function captureHttp(): self
    {
        return $this->set('http', 'capture');
    }

    public function blockHttp(): self
    {
        return $this->set('http', 'block');
    }

    public function allowHttp(): self
    {
        return $this->set('http', 'allow');
    }

    public function captureJobs(): self
    {
        return $this->set('queue', 'capture');
    }

    public function blockJobs(): self
    {
        return $this->set('queue', 'block');
    }

    public function allowJobs(): self
    {
        return $this->set('queue', 'allow');
    }

    public function captureEvents(): self
    {
        return $this->set('events', 'observe');
    }

    public function allowEvents(): self
    {
        return $this->set('events', 'allow');
    }

    public function captureMail(): self
    {
        return $this->set('mail', 'capture');
    }

    public function allowMail(): self
    {
        return $this->set('mail', 'allow');
    }

    public function captureNotifications(): self
    {
        return $this->set('notifications', 'capture');
    }

    public function allowNotifications(): self
    {
        return $this->set('notifications', 'allow');
    }

    public function isolateCacheWrites(): self
    {
        // Cache virtualization is only guaranteed by L3 / explicit adapters.
        return $this;
    }

    public function policyFor(string $effect): string
    {
        return (string) ($this->effects[$effect] ?? 'allow');
    }

    public function sandboxLevel(): SandboxLevel
    {
        return $this->level;
    }

    public function toArray(): array
    {
        return ['level' => $this->level->value] + $this->effects;
    }

    private function set(string $key, string $value): self
    {
        $this->effects[$key] = $value;

        return $this;
    }
}

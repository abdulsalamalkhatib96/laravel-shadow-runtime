<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Support;

final class SqlMutationDetector
{
    private const MUTATING_PREFIXES = [
        'insert', 'update', 'delete', 'replace', 'merge', 'upsert', 'truncate',
        'create', 'alter', 'drop', 'rename', 'grant', 'revoke', 'comment',
        'vacuum', 'analyze', 'refresh', 'call', 'exec', 'execute', 'copy',
        'load', 'lock', 'unlock', 'set',
    ];

    public function isMutation(string $sql): bool
    {
        $normalized = $this->stripLeadingCommentsAndWhitespace($sql);
        $first = strtolower((string) strtok($normalized, " \t\r\n("));

        if (in_array($first, self::MUTATING_PREFIXES, true)) {
            return true;
        }

        if ($first !== 'with') {
            return false;
        }

        // CTEs may still mutate: WITH ... UPDATE/INSERT/DELETE/MERGE ...
        return (bool) preg_match('/\b(insert|update|delete|merge|replace)\b/i', $normalized);
    }

    private function stripLeadingCommentsAndWhitespace(string $sql): string
    {
        $sql = ltrim($sql);

        do {
            $previous = $sql;
            $sql = preg_replace('/\A(?:--[^\n]*(?:\n|\z)|#[^\n]*(?:\n|\z)|\/\*.*?\*\/\s*)/s', '', $sql) ?? $sql;
            $sql = ltrim($sql);
        } while ($sql !== $previous);

        return $sql;
    }
}

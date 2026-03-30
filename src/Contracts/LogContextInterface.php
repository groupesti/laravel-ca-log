<?php

declare(strict_types=1);

namespace CA\Log\Contracts;

interface LogContextInterface
{
    /**
     * Enrich a log context array with CA-specific metadata.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function enrich(array $context): array;

    /**
     * Set a persistent context value that will be included in all subsequent log entries.
     */
    public function setPersistentContext(string $key, mixed $value): void;

    /**
     * Clear all persistent context values.
     */
    public function clearPersistentContext(): void;
}

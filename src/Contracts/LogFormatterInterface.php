<?php

declare(strict_types=1);

namespace CA\Log\Contracts;

use CA\Log\DTOs\LogEntry;

interface LogFormatterInterface
{
    /**
     * Format a log entry into its string representation.
     */
    public function format(LogEntry $entry): string;

    /**
     * Get the content type produced by this formatter.
     */
    public function contentType(): string;
}

<?php

declare(strict_types=1);

namespace CA\Log\Console\Commands;

use CA\Log\DTOs\LogFilter;
use CA\Log\Services\LogAggregator;
use DateTimeImmutable;
use Illuminate\Console\Command;

class LogSearchCommand extends Command
{
    protected $signature = 'ca-log:search
        {query : Search term to look for in log messages}
        {--operation= : Filter by operation type}
        {--level= : Filter by minimum log level}
        {--ca= : Filter by CA ID}
        {--serial= : Filter by certificate serial}
        {--from= : Start date (Y-m-d or ISO 8601)}
        {--to= : End date (Y-m-d or ISO 8601)}
        {--limit=50 : Maximum number of results}';

    protected $description = 'Search through CA operational logs';

    public function handle(LogAggregator $aggregator): int
    {
        $filter = new LogFilter(
            operations: $this->option('operation') ? [$this->option('operation')] : null,
            levels: $this->option('level') ? [$this->option('level')] : null,
            caId: $this->option('ca'),
            certificateSerial: $this->option('serial'),
            search: $this->argument('query'),
            from: $this->option('from') ? new DateTimeImmutable($this->option('from')) : null,
            to: $this->option('to') ? new DateTimeImmutable($this->option('to')) : null,
            limit: (int) $this->option('limit'),
        );

        $this->info('Searching CA logs...');
        $entries = $aggregator->query($filter);

        if ($entries->isEmpty()) {
            $this->warn('No matching log entries found.');

            return self::SUCCESS;
        }

        $this->info("Found {$entries->count()} entries:");
        $this->newLine();

        $rows = $entries->map(fn ($entry) => [
            $entry->timestamp->format('Y-m-d H:i:s'),
            strtoupper($entry->level),
            $entry->operation,
            $entry->caId ?? '-',
            mb_substr($entry->message, 0, 80),
        ])->toArray();

        $this->table(
            headers: ['Timestamp', 'Level', 'Operation', 'CA ID', 'Message'],
            rows: $rows,
        );

        return self::SUCCESS;
    }
}

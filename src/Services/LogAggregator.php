<?php

declare(strict_types=1);

namespace CA\Log\Services;

use CA\Log\DTOs\LogEntry;
use CA\Log\DTOs\LogFilter;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use SplFileObject;

class LogAggregator
{
    /**
     * Get log entries matching the given filter.
     *
     * @return Collection<int, LogEntry>
     */
    public function query(LogFilter $filter): Collection
    {
        $logPath = config('ca-log.path', storage_path('logs/ca'));
        $entries = collect();

        if (! is_dir($logPath)) {
            return $entries;
        }

        $files = glob($logPath.'/*.log') ?: [];
        rsort($files);

        foreach ($files as $file) {
            $fileEntries = $this->parseLogFile(filePath: $file, filter: $filter);
            $entries = $entries->merge($fileEntries);

            if ($entries->count() >= $filter->limit + $filter->offset) {
                break;
            }
        }

        return $entries
            ->sortByDesc(fn (LogEntry $entry): DateTimeImmutable => $entry->timestamp)
            ->skip($filter->offset)
            ->take($filter->limit)
            ->values();
    }

    /**
     * Get aggregated statistics for a given time period.
     *
     * @return array<string, mixed>
     */
    public function stats(?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array
    {
        $filter = new LogFilter(from: $from, to: $to, limit: PHP_INT_MAX);
        $entries = $this->query($filter);

        $byOperation = $entries->groupBy(fn (LogEntry $e): string => $e->operation)
            ->map(fn (Collection $group): int => $group->count())
            ->toArray();

        $byLevel = $entries->groupBy(fn (LogEntry $e): string => $e->level)
            ->map(fn (Collection $group): int => $group->count())
            ->toArray();

        $byCa = $entries->groupBy(fn (LogEntry $e): ?string => $e->caId)
            ->map(fn (Collection $group): int => $group->count())
            ->toArray();

        return [
            'total' => $entries->count(),
            'by_operation' => $byOperation,
            'by_level' => $byLevel,
            'by_ca' => $byCa,
            'period' => [
                'from' => $from?->format('c'),
                'to' => $to?->format('c'),
            ],
        ];
    }

    /**
     * Parse a log file and return entries matching the filter.
     *
     * @return Collection<int, LogEntry>
     */
    private function parseLogFile(string $filePath, LogFilter $filter): Collection
    {
        $entries = collect();

        if (! file_exists($filePath)) {
            return $entries;
        }

        $file = new SplFileObject($filePath, 'r');

        while (! $file->eof()) {
            $line = $file->fgets();

            if ($line === false || trim($line) === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (! is_array($decoded)) {
                continue;
            }

            $entry = LogEntry::fromArray($decoded);

            if ($filter->matches($entry)) {
                $entries->push($entry);
            }
        }

        return $entries;
    }
}

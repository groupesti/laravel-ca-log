<?php

declare(strict_types=1);

namespace CA\Log\Console\Commands;

use Illuminate\Console\Command;
use SplFileObject;

class LogTailCommand extends Command
{
    protected $signature = 'ca-log:tail
        {--lines=50 : Number of lines to display initially}
        {--operation= : Filter by operation type}
        {--level= : Filter by minimum log level}
        {--follow : Continuously follow new entries}';

    protected $description = 'Live tail CA operational logs';

    public function handle(): int
    {
        $logPath = config('ca-log.path', storage_path('logs/ca'));
        $logFile = rtrim($logPath, '/').'/ca.log';

        if (! file_exists($logFile)) {
            $this->error("Log file not found: {$logFile}");

            return self::FAILURE;
        }

        $lines = (int) $this->option('lines');
        $operation = $this->option('operation');
        $level = $this->option('level');
        $follow = (bool) $this->option('follow');

        $this->info("Tailing CA logs: {$logFile}");
        $this->newLine();

        $this->displayLastLines(
            filePath: $logFile,
            count: $lines,
            operation: $operation,
            level: $level,
        );

        if ($follow) {
            $this->followLog(
                filePath: $logFile,
                operation: $operation,
                level: $level,
            );
        }

        return self::SUCCESS;
    }

    private function displayLastLines(string $filePath, int $count, ?string $operation, ?string $level): void
    {
        $file = new SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $count * 3); // Read more lines to account for filtering
        $file->seek($startLine);

        $displayed = 0;
        while (! $file->eof() && $displayed < $count) {
            $line = $file->fgets();

            if ($line === false || trim($line) === '') {
                continue;
            }

            if ($this->matchesFilter(line: $line, operation: $operation, level: $level)) {
                $this->outputLine($line);
                $displayed++;
            }
        }
    }

    private function followLog(string $filePath, ?string $operation, ?string $level): void
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return;
        }

        fseek($handle, 0, SEEK_END);

        $this->info('Following... (Ctrl+C to stop)');

        while (true) {
            $line = fgets($handle);

            if ($line !== false && trim($line) !== '') {
                if ($this->matchesFilter(line: $line, operation: $operation, level: $level)) {
                    $this->outputLine($line);
                }
            } else {
                usleep(100_000); // 100ms
            }
        }
    }

    private function matchesFilter(string $line, ?string $operation, ?string $level): bool
    {
        if ($operation === null && $level === null) {
            return true;
        }

        $decoded = json_decode($line, true);

        if (! is_array($decoded)) {
            return false;
        }

        if ($operation !== null && ($decoded['operation'] ?? '') !== $operation) {
            return false;
        }

        if ($level !== null && ! $this->meetsLevel(entryLevel: $decoded['level'] ?? 'debug', minimum: $level)) {
            return false;
        }

        return true;
    }

    private function meetsLevel(string $entryLevel, string $minimum): bool
    {
        $priorities = [
            'debug' => 0, 'info' => 1, 'notice' => 2, 'warning' => 3,
            'error' => 4, 'critical' => 5, 'alert' => 6, 'emergency' => 7,
        ];

        return ($priorities[$entryLevel] ?? 0) >= ($priorities[$minimum] ?? 0);
    }

    private function outputLine(string $line): void
    {
        $decoded = json_decode($line, true);

        if (is_array($decoded)) {
            $timestamp = $decoded['@timestamp'] ?? $decoded['timestamp'] ?? '';
            $level = strtoupper($decoded['level'] ?? 'INFO');
            $operation = $decoded['operation'] ?? 'unknown';
            $message = $decoded['message'] ?? $line;

            $levelColor = match (strtolower($level)) {
                'emergency', 'alert', 'critical' => 'red',
                'error' => 'red',
                'warning' => 'yellow',
                'info' => 'green',
                default => 'white',
            };

            $this->line(sprintf(
                '<fg=gray>%s</> <fg=%s>%-9s</> <fg=cyan>[%s]</> %s',
                $timestamp,
                $levelColor,
                $level,
                $operation,
                $message,
            ));
        } else {
            $this->line(trim($line));
        }
    }
}

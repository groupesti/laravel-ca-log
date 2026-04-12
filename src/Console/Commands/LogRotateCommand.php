<?php

declare(strict_types=1);

namespace CA\Log\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogRotateCommand extends Command
{
    protected $signature = 'ca-log:rotate
        {--max-files= : Maximum number of rotated files to keep}
        {--max-size= : Maximum file size in MB before rotation}
        {--compress : Compress rotated files with gzip}
        {--dry-run : Show what would be done without making changes}';

    protected $description = 'Rotate CA log files';

    public function handle(): int
    {
        $logPath = config('ca-log.path', storage_path('logs/ca'));
        $maxFiles = (int) ($this->option('max-files') ?? config('ca-log.rotation.max_files', 30));
        $maxSizeMb = (int) ($this->option('max-size') ?? config('ca-log.rotation.max_size_mb', 100));
        $compress = (bool) ($this->option('compress') ?? config('ca-log.rotation.compress', true));
        $dryRun = (bool) $this->option('dry-run');

        if (! is_dir($logPath)) {
            $this->warn("Log directory does not exist: {$logPath}");

            return self::SUCCESS;
        }

        $this->info("Rotating logs in: {$logPath}");
        $this->info("Max files: {$maxFiles}, Max size: {$maxSizeMb}MB, Compress: ".($compress ? 'yes' : 'no'));

        $logFiles = glob($logPath.'/*.log') ?: [];

        if ($logFiles === []) {
            $this->info('No log files found.');

            return self::SUCCESS;
        }

        $rotated = 0;
        $deleted = 0;

        foreach ($logFiles as $logFile) {
            $sizeInMb = filesize($logFile) / 1024 / 1024;

            if ($sizeInMb >= $maxSizeMb) {
                $rotatedName = $logFile.'.'.date('Y-m-d-His');

                if ($dryRun) {
                    $this->line("[DRY RUN] Would rotate: {$logFile} (".number_format($sizeInMb, 1)."MB)");
                } else {
                    rename($logFile, $rotatedName);

                    if ($compress) {
                        $this->compressFile($rotatedName);
                        $rotatedName .= '.gz';
                    }

                    $this->line("Rotated: {$logFile} -> ".basename($rotatedName));
                    $rotated++;
                }
            }
        }

        // Clean up old rotated files
        $rotatedFiles = array_merge(
            glob($logPath.'/*.log.*') ?: [],
            glob($logPath.'/*.gz') ?: [],
        );

        usort($rotatedFiles, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        if (count($rotatedFiles) > $maxFiles) {
            $toDelete = array_slice($rotatedFiles, $maxFiles);

            foreach ($toDelete as $file) {
                if ($dryRun) {
                    $this->line("[DRY RUN] Would delete: ".basename($file));
                } else {
                    File::delete($file);
                    $this->line("Deleted old log: ".basename($file));
                    $deleted++;
                }
            }
        }

        $this->newLine();
        $this->info("Rotation complete. Rotated: {$rotated}, Deleted: {$deleted}");

        return self::SUCCESS;
    }

    private function compressFile(string $filePath): void
    {
        $gzFile = $filePath.'.gz';
        $handle = gzopen($gzFile, 'wb9');

        if ($handle === false) {
            return;
        }

        $content = file_get_contents($filePath);

        if ($content !== false) {
            gzwrite($handle, $content);
        }

        gzclose($handle);
        unlink($filePath);
    }
}

<?php

declare(strict_types=1);

namespace CA\Log\Channel;

use Illuminate\Log\LogManager;
use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class CaChannelFactory
{
    /**
     * Create a CA log channel using Monolog.
     *
     * @param  array<string, mixed>  $config
     */
    public function create(array $config = []): LoggerInterface
    {
        $logger = new Logger('ca');

        $path = $config['path'] ?? config('ca-log.path', storage_path('logs/ca'));
        $logFile = rtrim($path, '/').'/ca.log';
        $level = $this->parseLevel($config['level'] ?? 'debug');

        // Ensure the log directory exists
        $dir = dirname($logFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $maxFiles = (int) ($config['max_files'] ?? config('ca-log.rotation.max_files', 30));

        if ($maxFiles > 0) {
            $handler = new RotatingFileHandler(
                filename: $logFile,
                maxFiles: $maxFiles,
                level: $level,
            );
        } else {
            $handler = new StreamHandler(
                stream: $logFile,
                level: $level,
            );
        }

        $handler->setFormatter(new MonologJsonFormatter());
        $logger->pushHandler($handler);

        return $logger;
    }

    private function parseLevel(string $level): Level
    {
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Debug,
        };
    }
}

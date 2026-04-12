<?php

declare(strict_types=1);

namespace CA\Log\Exceptions;

use RuntimeException;

class LogException extends RuntimeException
{
    public static function logPathNotWritable(string $path): self
    {
        return new self("CA log path is not writable: {$path}");
    }

    public static function unsupportedFormat(string $format): self
    {
        return new self("Unsupported CA log format: {$format}. Supported formats: json, cef, syslog.");
    }

    public static function rotationFailed(string $file, string $reason): self
    {
        return new self("Failed to rotate log file {$file}: {$reason}");
    }
}

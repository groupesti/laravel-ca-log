<?php

declare(strict_types=1);

namespace CA\Log\Formatters;

use CA\Log\Contracts\LogFormatterInterface;
use CA\Log\DTOs\LogEntry;

class JsonFormatter implements LogFormatterInterface
{
    public function format(LogEntry $entry): string
    {
        $data = [
            '@timestamp' => $entry->timestamp->format('c'),
            'level' => $entry->level,
            'operation' => $entry->operation,
            'message' => $entry->message,
            'ca_id' => $entry->caId,
            'certificate_serial' => $entry->certificateSerial,
            'request_ip' => $entry->requestIp,
            'user_id' => $entry->userId,
            'context' => $entry->context,
        ];

        return json_encode(
            value: array_filter($data, fn (mixed $v): bool => $v !== null && $v !== []),
            flags: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) ?: '{}';
    }

    public function contentType(): string
    {
        return 'application/json';
    }
}

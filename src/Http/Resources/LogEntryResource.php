<?php

declare(strict_types=1);

namespace CA\Log\Http\Resources;

use CA\Log\DTOs\LogEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LogEntry
 */
class LogEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LogEntry $entry */
        $entry = $this->resource;

        return [
            'operation' => $entry->operation,
            'level' => $entry->level,
            'message' => $entry->message,
            'timestamp' => $entry->timestamp->format('c'),
            'ca_id' => $entry->caId,
            'certificate_serial' => $entry->certificateSerial,
            'request_ip' => $entry->requestIp,
            'user_id' => $entry->userId,
            'context' => $entry->context,
        ];
    }
}

<?php

declare(strict_types=1);

namespace CA\Log\DTOs;

use DateTimeImmutable;

readonly class LogEntry
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $operation,
        public string $level,
        public string $message,
        public array $context = [],
        public DateTimeImmutable $timestamp = new DateTimeImmutable(),
        public ?string $caId = null,
        public ?string $certificateSerial = null,
        public ?string $requestIp = null,
        public ?string $userId = null,
    ) {}

    /**
     * Create a LogEntry from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            operation: $data['operation'] ?? 'unknown',
            level: $data['level'] ?? 'info',
            message: $data['message'] ?? '',
            context: $data['context'] ?? [],
            timestamp: isset($data['timestamp'])
                ? new DateTimeImmutable($data['timestamp'])
                : new DateTimeImmutable(),
            caId: $data['ca_id'] ?? null,
            certificateSerial: $data['certificate_serial'] ?? null,
            requestIp: $data['request_ip'] ?? null,
            userId: $data['user_id'] ?? null,
        );
    }

    /**
     * Convert the log entry to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
            'timestamp' => $this->timestamp->format('c'),
            'ca_id' => $this->caId,
            'certificate_serial' => $this->certificateSerial,
            'request_ip' => $this->requestIp,
            'user_id' => $this->userId,
        ];
    }
}

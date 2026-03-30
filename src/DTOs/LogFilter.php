<?php

declare(strict_types=1);

namespace CA\Log\DTOs;

use DateTimeImmutable;

readonly class LogFilter
{
    /**
     * @param  list<string>|null  $operations
     * @param  list<string>|null  $levels
     */
    public function __construct(
        public ?array $operations = null,
        public ?array $levels = null,
        public ?string $caId = null,
        public ?string $certificateSerial = null,
        public ?string $search = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
        public int $limit = 100,
        public int $offset = 0,
    ) {}

    /**
     * Create a LogFilter from a request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            operations: $data['operations'] ?? null,
            levels: $data['levels'] ?? null,
            caId: $data['ca_id'] ?? null,
            certificateSerial: $data['certificate_serial'] ?? null,
            search: $data['search'] ?? null,
            from: isset($data['from']) ? new DateTimeImmutable($data['from']) : null,
            to: isset($data['to']) ? new DateTimeImmutable($data['to']) : null,
            limit: (int) ($data['limit'] ?? 100),
            offset: (int) ($data['offset'] ?? 0),
        );
    }

    /**
     * Check if a log entry matches this filter.
     */
    public function matches(LogEntry $entry): bool
    {
        if ($this->operations !== null && ! in_array($entry->operation, $this->operations, true)) {
            return false;
        }

        if ($this->levels !== null && ! in_array($entry->level, $this->levels, true)) {
            return false;
        }

        if ($this->caId !== null && $entry->caId !== $this->caId) {
            return false;
        }

        if ($this->certificateSerial !== null && $entry->certificateSerial !== $this->certificateSerial) {
            return false;
        }

        if ($this->search !== null && ! str_contains($entry->message, $this->search)) {
            return false;
        }

        if ($this->from !== null && $entry->timestamp < $this->from) {
            return false;
        }

        if ($this->to !== null && $entry->timestamp > $this->to) {
            return false;
        }

        return true;
    }
}

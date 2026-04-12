<?php

declare(strict_types=1);

namespace CA\Log\Events;

use CA\Log\DTOs\LogEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriticalLogRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly LogEntry $entry,
    ) {}
}

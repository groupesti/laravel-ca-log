<?php

declare(strict_types=1);

namespace CA\Log\Http\Controllers;

use CA\Log\DTOs\LogFilter;
use CA\Log\Http\Resources\LogEntryResource;
use CA\Log\Services\LogAggregator;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class LogController extends Controller
{
    public function __construct(
        private readonly LogAggregator $aggregator,
    ) {}

    /**
     * Query log entries with optional filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filter = new LogFilter(
            operations: $request->input('operations'),
            levels: $request->input('levels'),
            caId: $request->input('ca_id'),
            certificateSerial: $request->input('certificate_serial'),
            search: $request->input('search'),
            from: $request->filled('from') ? new DateTimeImmutable($request->input('from')) : null,
            to: $request->filled('to') ? new DateTimeImmutable($request->input('to')) : null,
            limit: (int) $request->input('limit', 100),
            offset: (int) $request->input('offset', 0),
        );

        $entries = $this->aggregator->query($filter);

        return LogEntryResource::collection($entries);
    }

    /**
     * Get aggregated log statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? new DateTimeImmutable($request->input('from')) : null;
        $to = $request->filled('to') ? new DateTimeImmutable($request->input('to')) : null;

        $stats = $this->aggregator->stats(from: $from, to: $to);

        return response()->json(['data' => $stats]);
    }
}

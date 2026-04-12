<?php

declare(strict_types=1);

namespace CA\Log\Services;

use CA\Log\Contracts\LogContextInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContextEnricher implements LogContextInterface
{
    /** @var array<string, mixed> */
    private array $persistentContext = [];

    public function __construct(
        private readonly Request $request,
    ) {}

    /**
     * @inheritDoc
     */
    public function enrich(array $context): array
    {
        $enriched = $this->persistentContext;

        if (config('ca-log.enrichment.enabled', true)) {
            $enriched['timestamp'] = now()->toIso8601String();
            $enriched['environment'] = app()->environment();
            $enriched['hostname'] = gethostname() ?: 'unknown';

            if (config('ca-log.enrichment.include_request_ip', true)) {
                $enriched['request_ip'] = $this->request->ip();
            }

            if (config('ca-log.enrichment.include_user_agent', false)) {
                $enriched['user_agent'] = $this->request->userAgent();
            }

            if (config('ca-log.enrichment.include_authenticated_user', true) && Auth::check()) {
                $enriched['user_id'] = (string) Auth::id();
                $enriched['user_email'] = Auth::user()?->email ?? null;
            }
        }

        return [...$enriched, ...$context];
    }

    public function setPersistentContext(string $key, mixed $value): void
    {
        $this->persistentContext[$key] = $value;
    }

    public function clearPersistentContext(): void
    {
        $this->persistentContext = [];
    }
}

<?php

declare(strict_types=1);

namespace CA\Log\Services;

use CA\Log\Contracts\LogContextInterface;
use CA\Log\Contracts\LogFormatterInterface;
use CA\Log\DTOs\LogEntry;
use CA\Log\Events\CriticalLogRecorded;
use Illuminate\Support\Facades\Log;

class CaLogger
{
    public function __construct(
        private readonly LogFormatterInterface $formatter,
        private readonly LogContextInterface $enricher,
    ) {}

    /**
     * Log a CA operation.
     *
     * @param  array<string, mixed>  $context
     */
    public function log(string $operation, string $level, string $message, array $context = []): void
    {
        $configuredLevel = config("ca-log.levels.{$operation}", 'info');

        if (! $this->shouldLog(level: $level, configuredMinimum: $configuredLevel)) {
            return;
        }

        $enrichedContext = $this->enricher->enrich($context);

        $entry = new LogEntry(
            operation: $operation,
            level: $level,
            message: $message,
            context: $enrichedContext,
            caId: $enrichedContext['ca_id'] ?? null,
            certificateSerial: $enrichedContext['certificate_serial'] ?? null,
            requestIp: $enrichedContext['request_ip'] ?? null,
            userId: $enrichedContext['user_id'] ?? null,
        );

        $formatted = $this->formatter->format($entry);
        $channel = config('ca-log.default_channel', 'ca');

        Log::channel($channel)->log($level, $formatted, $enrichedContext);

        if ($this->isCriticalLevel($level) && config('ca-log.dispatch_critical_events', true)) {
            event(new CriticalLogRecorded(entry: $entry));
        }
    }

    /**
     * Log a certificate issuance event.
     *
     * @param  array<string, mixed>  $context
     */
    public function certificateIssued(string $serial, string $subject, array $context = []): void
    {
        $this->log(
            operation: 'certificate_issuance',
            level: 'info',
            message: "Certificate issued: {$subject} (serial: {$serial})",
            context: ['certificate_serial' => $serial, 'subject' => $subject, ...$context],
        );
    }

    /**
     * Log a certificate revocation event.
     *
     * @param  array<string, mixed>  $context
     */
    public function certificateRevoked(string $serial, string $reason, array $context = []): void
    {
        $this->log(
            operation: 'certificate_revocation',
            level: 'warning',
            message: "Certificate revoked: {$serial} (reason: {$reason})",
            context: ['certificate_serial' => $serial, 'revocation_reason' => $reason, ...$context],
        );
    }

    /**
     * Log a key generation event.
     *
     * @param  array<string, mixed>  $context
     */
    public function keyGenerated(string $algorithm, int $keySize, array $context = []): void
    {
        $this->log(
            operation: 'key_generation',
            level: 'info',
            message: "Key generated: {$algorithm} ({$keySize} bits)",
            context: ['algorithm' => $algorithm, 'key_size' => $keySize, ...$context],
        );
    }

    /**
     * Log a CRL update event.
     *
     * @param  array<string, mixed>  $context
     */
    public function crlUpdated(string $caId, int $entriesCount, array $context = []): void
    {
        $this->log(
            operation: 'crl_update',
            level: 'info',
            message: "CRL updated for CA {$caId}: {$entriesCount} entries",
            context: ['ca_id' => $caId, 'entries_count' => $entriesCount, ...$context],
        );
    }

    /**
     * Log an OCSP response event.
     *
     * @param  array<string, mixed>  $context
     */
    public function ocspResponse(string $serial, string $status, array $context = []): void
    {
        $this->log(
            operation: 'ocsp_response',
            level: 'info',
            message: "OCSP response for {$serial}: {$status}",
            context: ['certificate_serial' => $serial, 'ocsp_status' => $status, ...$context],
        );
    }

    /**
     * Log an ACME protocol operation.
     *
     * @param  array<string, mixed>  $context
     */
    public function acmeOperation(string $action, array $context = []): void
    {
        $this->log(
            operation: 'acme_operation',
            level: 'info',
            message: "ACME: {$action}",
            context: ['acme_action' => $action, ...$context],
        );
    }

    /**
     * Log a SCEP protocol operation.
     *
     * @param  array<string, mixed>  $context
     */
    public function scepOperation(string $action, array $context = []): void
    {
        $this->log(
            operation: 'scep_operation',
            level: 'info',
            message: "SCEP: {$action}",
            context: ['scep_action' => $action, ...$context],
        );
    }

    /**
     * Log an EST protocol operation.
     *
     * @param  array<string, mixed>  $context
     */
    public function estOperation(string $action, array $context = []): void
    {
        $this->log(
            operation: 'est_operation',
            level: 'info',
            message: "EST: {$action}",
            context: ['est_action' => $action, ...$context],
        );
    }

    /**
     * Log a critical message.
     *
     * @param  array<string, mixed>  $context
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(
            operation: $context['operation'] ?? 'critical',
            level: 'critical',
            message: $message,
            context: $context,
        );
    }

    /**
     * Determine if the given level meets the configured minimum.
     */
    private function shouldLog(string $level, string $configuredMinimum): bool
    {
        $priorities = [
            'debug' => 0,
            'info' => 1,
            'notice' => 2,
            'warning' => 3,
            'error' => 4,
            'critical' => 5,
            'alert' => 6,
            'emergency' => 7,
        ];

        return ($priorities[$level] ?? 0) >= ($priorities[$configuredMinimum] ?? 0);
    }

    private function isCriticalLevel(string $level): bool
    {
        return in_array($level, ['critical', 'alert', 'emergency'], true);
    }
}

<?php

declare(strict_types=1);

namespace CA\Log\Formatters;

use CA\Log\Contracts\LogFormatterInterface;
use CA\Log\DTOs\LogEntry;

/**
 * Common Event Format (CEF) formatter for SIEM integration.
 *
 * Format: CEF:Version|Device Vendor|Device Product|Device Version|Signature ID|Name|Severity|Extension
 *
 * @see https://www.microfocus.com/documentation/arcsight/arcsight-smartconnectors/pdfdoc/cef-implementation-standard/cef-implementation-standard.pdf
 */
class CefFormatter implements LogFormatterInterface
{
    private const array SEVERITY_MAP = [
        'debug' => 0,
        'info' => 3,
        'notice' => 4,
        'warning' => 6,
        'error' => 7,
        'critical' => 8,
        'alert' => 9,
        'emergency' => 10,
    ];

    public function format(LogEntry $entry): string
    {
        $vendor = config('ca-log.cef.device_vendor', 'GroupeSTI');
        $product = config('ca-log.cef.device_product', 'LaravelCA');
        $version = config('ca-log.cef.device_version', '0.1.0');
        $signatureId = $this->signatureId($entry->operation);
        $severity = self::SEVERITY_MAP[$entry->level] ?? 3;

        $extensions = $this->buildExtensions($entry);

        return sprintf(
            'CEF:0|%s|%s|%s|%s|%s|%d|%s',
            $this->escape($vendor),
            $this->escape($product),
            $this->escape($version),
            $signatureId,
            $this->escape($entry->message),
            $severity,
            $extensions,
        );
    }

    public function contentType(): string
    {
        return 'text/plain';
    }

    private function signatureId(string $operation): string
    {
        return match ($operation) {
            'certificate_issuance' => 'CA-001',
            'certificate_revocation' => 'CA-002',
            'key_generation' => 'CA-003',
            'crl_update' => 'CA-004',
            'ocsp_response' => 'CA-005',
            'acme_operation' => 'CA-006',
            'scep_operation' => 'CA-007',
            'est_operation' => 'CA-008',
            'tsa_operation' => 'CA-009',
            'policy_evaluation' => 'CA-010',
            default => 'CA-999',
        };
    }

    private function buildExtensions(LogEntry $entry): string
    {
        $ext = [];
        $ext[] = 'rt='.$entry->timestamp->getTimestamp() * 1000;
        $ext[] = 'cat='.$entry->operation;

        if ($entry->caId !== null) {
            $ext[] = 'cs1='.$this->escapeValue($entry->caId);
            $ext[] = 'cs1Label=CA ID';
        }

        if ($entry->certificateSerial !== null) {
            $ext[] = 'cs2='.$this->escapeValue($entry->certificateSerial);
            $ext[] = 'cs2Label=Certificate Serial';
        }

        if ($entry->requestIp !== null) {
            $ext[] = 'src='.$entry->requestIp;
        }

        if ($entry->userId !== null) {
            $ext[] = 'suid='.$this->escapeValue($entry->userId);
        }

        return implode(' ', $ext);
    }

    private function escape(string $value): string
    {
        return str_replace(
            search: ['\\', '|'],
            replace: ['\\\\', '\\|'],
            subject: $value,
        );
    }

    private function escapeValue(string $value): string
    {
        return str_replace(
            search: ['\\', '=', "\n", "\r"],
            replace: ['\\\\', '\\=', '\\n', '\\r'],
            subject: $value,
        );
    }
}

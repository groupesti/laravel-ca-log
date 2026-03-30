<?php

declare(strict_types=1);

namespace CA\Log\Formatters;

use CA\Log\Contracts\LogFormatterInterface;
use CA\Log\DTOs\LogEntry;

/**
 * RFC 5424 structured syslog format.
 *
 * Format: <PRI>VERSION TIMESTAMP HOSTNAME APP-NAME PROCID MSGID [SD-ID SD-PARAM] MSG
 *
 * @see https://datatracker.ietf.org/doc/html/rfc5424
 */
class SyslogFormatter implements LogFormatterInterface
{
    private const array FACILITY_USER = [
        'debug' => 15,     // <user.debug>
        'info' => 14,      // <user.info>
        'notice' => 13,    // <user.notice>
        'warning' => 12,   // <user.warning>
        'error' => 11,     // <user.err>
        'critical' => 10,  // <user.crit>
        'alert' => 9,      // <user.alert>
        'emergency' => 8,  // <user.emerg>
    ];

    public function format(LogEntry $entry): string
    {
        $priority = $this->calculatePriority($entry->level);
        $timestamp = $entry->timestamp->format('Y-m-d\TH:i:s.uP');
        $hostname = gethostname() ?: '-';
        $appName = 'laravel-ca';
        $procId = (string) getmypid();
        $msgId = strtoupper(str_replace('_', '-', $entry->operation));

        $structuredData = $this->buildStructuredData($entry);

        return sprintf(
            '<%d>1 %s %s %s %s %s %s %s',
            $priority,
            $timestamp,
            $hostname,
            $appName,
            $procId,
            $msgId,
            $structuredData,
            $entry->message,
        );
    }

    public function contentType(): string
    {
        return 'text/plain';
    }

    private function calculatePriority(string $level): int
    {
        // Facility 1 (user-level) = 8, plus severity
        $severity = self::FACILITY_USER[$level] ?? 14;
        // Priority = Facility * 8 + Severity
        $facilityCode = 1; // user-level

        return $facilityCode * 8 + (15 - $severity);
    }

    private function buildStructuredData(LogEntry $entry): string
    {
        $params = [];

        if ($entry->caId !== null) {
            $params[] = 'caId="'.$this->escapeParam($entry->caId).'"';
        }

        if ($entry->certificateSerial !== null) {
            $params[] = 'certSerial="'.$this->escapeParam($entry->certificateSerial).'"';
        }

        if ($entry->requestIp !== null) {
            $params[] = 'srcIp="'.$this->escapeParam($entry->requestIp).'"';
        }

        if ($entry->userId !== null) {
            $params[] = 'userId="'.$this->escapeParam($entry->userId).'"';
        }

        if ($params === []) {
            return '-';
        }

        return '[ca@0 '.implode(' ', $params).']';
    }

    private function escapeParam(string $value): string
    {
        return str_replace(
            search: ['\\', '"', ']'],
            replace: ['\\\\', '\\"', '\\]'],
            subject: $value,
        );
    }
}

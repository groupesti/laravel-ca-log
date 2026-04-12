<?php

declare(strict_types=1);

use CA\Log\DTOs\LogEntry;
use CA\Log\Formatters\CefFormatter;
use DateTimeImmutable;

beforeEach(function (): void {
    config()->set('ca-log.cef.device_vendor', 'GroupeSTI');
    config()->set('ca-log.cef.device_product', 'LaravelCA');
    config()->set('ca-log.cef.device_version', '0.1.0');
});

it('formats a log entry in CEF format', function (): void {
    $formatter = new CefFormatter();
    $entry = new LogEntry(
        operation: 'certificate_issuance',
        level: 'info',
        message: 'Certificate issued for CN=test',
        timestamp: new DateTimeImmutable('2026-03-29T10:00:00+00:00'),
        caId: 'root-ca-001',
        certificateSerial: 'ABCDEF1234',
    );

    $result = $formatter->format($entry);

    expect($result)
        ->toStartWith('CEF:0|GroupeSTI|LaravelCA|0.1.0|CA-001|')
        ->toContain('Certificate issued for CN=test')
        ->toContain('cs1=root-ca-001')
        ->toContain('cs1Label=CA ID')
        ->toContain('cs2=ABCDEF1234')
        ->toContain('cs2Label=Certificate Serial')
        ->toContain('cat=certificate_issuance');
});

it('maps severity levels correctly', function (): void {
    $formatter = new CefFormatter();

    $criticalEntry = new LogEntry(
        operation: 'certificate_issuance',
        level: 'critical',
        message: 'Critical event',
        timestamp: new DateTimeImmutable('2026-03-29T10:00:00+00:00'),
    );

    $debugEntry = new LogEntry(
        operation: 'certificate_issuance',
        level: 'debug',
        message: 'Debug event',
        timestamp: new DateTimeImmutable('2026-03-29T10:00:00+00:00'),
    );

    $criticalResult = $formatter->format($criticalEntry);
    $debugResult = $formatter->format($debugEntry);

    // Critical severity = 8, Debug severity = 0
    expect($criticalResult)->toContain('|8|');
    expect($debugResult)->toContain('|0|');
});

it('assigns correct signature IDs per operation', function (string $operation, string $expectedId): void {
    $formatter = new CefFormatter();
    $entry = new LogEntry(
        operation: $operation,
        level: 'info',
        message: 'Test',
        timestamp: new DateTimeImmutable('2026-03-29T10:00:00+00:00'),
    );

    $result = $formatter->format($entry);

    expect($result)->toContain("|{$expectedId}|");
})->with([
    ['certificate_issuance', 'CA-001'],
    ['certificate_revocation', 'CA-002'],
    ['key_generation', 'CA-003'],
    ['crl_update', 'CA-004'],
    ['ocsp_response', 'CA-005'],
    ['acme_operation', 'CA-006'],
    ['scep_operation', 'CA-007'],
    ['est_operation', 'CA-008'],
]);

it('escapes pipe characters in CEF header fields', function (): void {
    $formatter = new CefFormatter();
    $entry = new LogEntry(
        operation: 'certificate_issuance',
        level: 'info',
        message: 'Message with | pipe',
        timestamp: new DateTimeImmutable('2026-03-29T10:00:00+00:00'),
    );

    $result = $formatter->format($entry);

    expect($result)->toContain('Message with \\| pipe');
});

it('includes source IP in extensions', function (): void {
    $formatter = new CefFormatter();
    $entry = new LogEntry(
        operation: 'acme_operation',
        level: 'info',
        message: 'ACME request',
        timestamp: new DateTimeImmutable('2026-03-29T10:00:00+00:00'),
        requestIp: '10.0.0.1',
    );

    $result = $formatter->format($entry);

    expect($result)->toContain('src=10.0.0.1');
});

it('returns text/plain content type', function (): void {
    $formatter = new CefFormatter();

    expect($formatter->contentType())->toBe('text/plain');
});

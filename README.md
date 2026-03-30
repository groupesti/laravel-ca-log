# Laravel CA Log

> Structured operational logging for Laravel CA — channels, formatters, context enrichment, and log management commands.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/groupesti/laravel-ca-log.svg)](https://packagist.org/packages/groupesti/laravel-ca-log)
[![PHP Version](https://img.shields.io/badge/php-8.4%2B-blue)](https://www.php.net/releases/8.4/en.php)
[![Laravel](https://img.shields.io/badge/laravel-12.x%20|%2013.x-red)](https://laravel.com)
[![Tests](https://github.com/groupesti/laravel-ca-log/actions/workflows/tests.yml/badge.svg)](https://github.com/groupesti/laravel-ca-log/actions/workflows/tests.yml)
[![License](https://img.shields.io/github/license/groupesti/laravel-ca-log)](LICENSE.md)

## Requirements

- **PHP** 8.4+
- **Laravel** 12.x or 13.x
- **groupesti/laravel-ca** ^0.1
- PHP extensions: `json`, `mbstring`

## Installation

```bash
composer require groupesti/laravel-ca-log
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ca-log-config
```

## Configuration

The configuration file is published to `config/ca-log.php`. Key options:

| Key | Default | Description |
|-----|---------|-------------|
| `default_channel` | `ca` | The Laravel log channel name for CA logs |
| `path` | `storage/logs/ca` | Directory where CA log files are stored |
| `format` | `json` | Default formatter: `json`, `cef`, or `syslog` |
| `levels.*` | `info` | Minimum log level per CA operation type |
| `enrichment.enabled` | `true` | Auto-enrich logs with CA metadata |
| `enrichment.include_request_ip` | `true` | Include requester IP in context |
| `enrichment.include_authenticated_user` | `true` | Include authenticated user info |
| `cef.device_vendor` | `GroupeSTI` | CEF vendor name for SIEM integration |
| `cef.device_product` | `LaravelCA` | CEF product name |
| `rotation.max_files` | `30` | Max rotated log files to keep |
| `rotation.max_size_mb` | `100` | Max file size before rotation |
| `rotation.compress` | `true` | Gzip compress rotated files |
| `dispatch_critical_events` | `true` | Dispatch events on critical log entries |
| `routes.enabled` | `true` | Enable the log query API |
| `routes.prefix` | `api/ca` | API route prefix |

### Log Levels per Operation

Configure minimum log levels for each CA operation type in the `levels` array:

```php
'levels' => [
    'certificate_issuance'   => 'info',
    'certificate_revocation' => 'info',
    'key_generation'         => 'info',
    'crl_update'             => 'info',
    'ocsp_response'          => 'info',
    'acme_operation'         => 'info',
    'scep_operation'         => 'info',
    'est_operation'          => 'info',
    'tsa_operation'          => 'info',
    'policy_evaluation'      => 'debug',
],
```

## Usage

### CaLogger Service

Use the `CaLog` facade or inject `CaLogger` directly:

```php
use CA\Log\Facades\CaLog;

// Log a certificate issuance
CaLog::certificateIssued(
    serial: 'ABCDEF1234',
    subject: 'CN=example.com,O=Acme Inc',
    context: ['key_algorithm' => 'RSA', 'key_size' => 4096],
);

// Log a certificate revocation
CaLog::certificateRevoked(
    serial: 'ABCDEF1234',
    reason: 'keyCompromise',
);

// Log key generation
CaLog::keyGenerated(algorithm: 'EC', keySize: 384);

// Log CRL update
CaLog::crlUpdated(caId: 'root-ca-001', entriesCount: 42);

// Log OCSP response
CaLog::ocspResponse(serial: 'ABCDEF1234', status: 'good');

// Log protocol operations
CaLog::acmeOperation(action: 'newOrder', context: ['domain' => 'example.com']);
CaLog::scepOperation(action: 'PKCSReq', context: ['transaction_id' => 'TX001']);
CaLog::estOperation(action: 'simpleenroll', context: ['client_cert' => 'CN=device']);

// Generic log with custom operation
CaLog::log(
    operation: 'certificate_issuance',
    level: 'warning',
    message: 'Issuance delayed due to policy check',
    context: ['delay_ms' => 1500],
);

// Critical log (auto-dispatches CriticalLogRecorded event)
CaLog::critical(message: 'HSM connection lost', context: ['hsm_id' => 'hsm-01']);
```

### Log Formatters

Three formatters are available, selected via the `ca-log.format` config key:

**JSON (default)** — Structured JSON with `@timestamp`, `level`, `operation`, `message`, and context fields.

**CEF (Common Event Format)** — For SIEM integration (ArcSight, Splunk, QRadar). Includes signature IDs per operation type (CA-001 through CA-010).

**Syslog (RFC 5424)** — Structured syslog format with CA-specific structured data elements.

### Context Enrichment

When `enrichment.enabled` is `true`, every log entry is automatically enriched with:

- `timestamp` — ISO 8601 timestamp
- `environment` — Application environment (production, staging, etc.)
- `hostname` — Server hostname
- `request_ip` — Requester IP address (configurable)
- `user_id` / `user_email` — Authenticated user info (configurable)

Set persistent context for a request lifecycle:

```php
use CA\Log\Contracts\LogContextInterface;

$enricher = app(LogContextInterface::class);
$enricher->setPersistentContext('ca_id', 'root-ca-001');
$enricher->setPersistentContext('request_id', Str::uuid()->toString());
```

### Log Aggregation

Query and aggregate logs for dashboards:

```php
use CA\Log\Services\LogAggregator;
use CA\Log\DTOs\LogFilter;

$aggregator = app(LogAggregator::class);

// Query with filters
$entries = $aggregator->query(new LogFilter(
    operations: ['certificate_issuance', 'certificate_revocation'],
    levels: ['warning', 'error', 'critical'],
    caId: 'root-ca-001',
    from: new DateTimeImmutable('-24 hours'),
    limit: 50,
));

// Get statistics
$stats = $aggregator->stats(
    from: new DateTimeImmutable('-7 days'),
);
// Returns: ['total' => 1234, 'by_operation' => [...], 'by_level' => [...], 'by_ca' => [...]]
```

### Artisan Commands

```bash
# Live tail CA logs
php artisan ca-log:tail --lines=100 --operation=certificate_issuance --follow

# Search through logs
php artisan ca-log:search "CN=example" --level=warning --from=2026-03-01 --limit=50

# Rotate log files
php artisan ca-log:rotate --max-files=30 --max-size=100 --compress
php artisan ca-log:rotate --dry-run
```

### API Endpoints

When `routes.enabled` is `true`, these endpoints are available:

| Method | URI | Description |
|--------|-----|-------------|
| `GET` | `/api/ca/logs` | Query log entries with filters |
| `GET` | `/api/ca/logs/stats` | Get aggregated log statistics |

Query parameters for `/api/ca/logs`: `operations[]`, `levels[]`, `ca_id`, `certificate_serial`, `search`, `from`, `to`, `limit`, `offset`.

### Custom Channel Driver

The package registers a `ca` custom channel driver. Add it to your `config/logging.php`:

```php
'channels' => [
    'ca' => [
        'driver' => 'ca',
        'path' => storage_path('logs/ca'),
        'level' => 'debug',
        'max_files' => 30,
    ],
],
```

### Critical Event Handling

When `dispatch_critical_events` is enabled, critical/alert/emergency log entries automatically dispatch the `CriticalLogRecorded` event:

```php
use CA\Log\Events\CriticalLogRecorded;

Event::listen(CriticalLogRecorded::class, function (CriticalLogRecorded $event): void {
    // Send alert, notify Slack, page on-call, etc.
    Notification::send($admins, new CriticalCaAlert($event->entry));
});
```

## Testing

```bash
./vendor/bin/pest
./vendor/bin/pest --coverage
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@groupesti.com instead of using the issue tracker. Please see [SECURITY.md](SECURITY.md) for details.

## Credits

- [Groupe STI](https://github.com/groupesti)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

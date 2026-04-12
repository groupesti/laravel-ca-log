# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-03-29

### Added

- Initial release of `laravel-ca-log` package.
- `CaLogger` service with dedicated methods for CA operations: certificate issuance, revocation, key generation, CRL updates, OCSP responses, ACME/SCEP/EST protocol operations.
- `CaLog` facade for convenient static access to the logger.
- Three log formatters: `JsonFormatter` (structured JSON), `CefFormatter` (Common Event Format for SIEM), `SyslogFormatter` (RFC 5424).
- `ContextEnricher` service for automatic log context enrichment with CA metadata (ca_id, certificate_serial, request IP, authenticated user).
- Persistent context support for request-scoped metadata.
- `LogAggregator` service for querying and aggregating log entries for dashboards.
- `LogFilter` and `LogEntry` readonly DTOs.
- `CaChannelFactory` custom Laravel log channel driver with Monolog rotating file handler.
- Configurable log levels per CA operation type.
- `ca-log:tail` Artisan command for live tailing CA logs with operation and level filters.
- `ca-log:search` Artisan command for searching through CA logs.
- `ca-log:rotate` Artisan command for log file rotation with compression support.
- REST API endpoints for querying logs (`GET /api/ca/logs`) and statistics (`GET /api/ca/logs/stats`).
- `CriticalLogRecorded` event dispatched on critical/alert/emergency log entries.
- `LogException` with factory methods for common error scenarios.
- Full configuration file with environment variable support for all options.

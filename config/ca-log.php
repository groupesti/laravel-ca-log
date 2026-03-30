<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default CA Log Channel
    |--------------------------------------------------------------------------
    |
    | The default log channel used for CA operational logging. This channel
    | is registered as a custom driver in Laravel's logging system.
    |
    */

    'default_channel' => env('CA_LOG_CHANNEL', 'ca'),

    /*
    |--------------------------------------------------------------------------
    | Log Path
    |--------------------------------------------------------------------------
    |
    | The directory where CA log files are stored. Defaults to the standard
    | Laravel log directory with a "ca" subdirectory.
    |
    */

    'path' => env('CA_LOG_PATH', storage_path('logs/ca')),

    /*
    |--------------------------------------------------------------------------
    | Log Format
    |--------------------------------------------------------------------------
    |
    | The default formatter to use for CA log output. Supported values:
    | "json", "cef", "syslog"
    |
    */

    'format' => env('CA_LOG_FORMAT', 'json'),

    /*
    |--------------------------------------------------------------------------
    | Log Levels per Operation Type
    |--------------------------------------------------------------------------
    |
    | Configure the minimum log level for each CA operation type.
    | Supported levels: emergency, alert, critical, error, warning,
    | notice, info, debug
    |
    */

    'levels' => [
        'certificate_issuance' => env('CA_LOG_LEVEL_CERT_ISSUANCE', 'info'),
        'certificate_revocation' => env('CA_LOG_LEVEL_CERT_REVOCATION', 'info'),
        'key_generation' => env('CA_LOG_LEVEL_KEY_GENERATION', 'info'),
        'crl_update' => env('CA_LOG_LEVEL_CRL_UPDATE', 'info'),
        'ocsp_response' => env('CA_LOG_LEVEL_OCSP_RESPONSE', 'info'),
        'acme_operation' => env('CA_LOG_LEVEL_ACME', 'info'),
        'scep_operation' => env('CA_LOG_LEVEL_SCEP', 'info'),
        'est_operation' => env('CA_LOG_LEVEL_EST', 'info'),
        'tsa_operation' => env('CA_LOG_LEVEL_TSA', 'info'),
        'policy_evaluation' => env('CA_LOG_LEVEL_POLICY', 'debug'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Enrichment
    |--------------------------------------------------------------------------
    |
    | Enable automatic context enrichment. When enabled, log entries are
    | automatically enriched with CA metadata (ca_id, certificate_serial,
    | requester IP, etc.).
    |
    */

    'enrichment' => [
        'enabled' => env('CA_LOG_ENRICHMENT', true),
        'include_request_ip' => true,
        'include_user_agent' => false,
        'include_authenticated_user' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | CEF Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Common Event Format (CEF) output, typically used
    | for SIEM integration.
    |
    */

    'cef' => [
        'device_vendor' => env('CA_LOG_CEF_VENDOR', 'GroupeSTI'),
        'device_product' => env('CA_LOG_CEF_PRODUCT', 'LaravelCA'),
        'device_version' => env('CA_LOG_CEF_VERSION', '0.1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Rotation
    |--------------------------------------------------------------------------
    |
    | Settings for log file rotation managed by the ca-log:rotate command.
    |
    */

    'rotation' => [
        'max_files' => env('CA_LOG_MAX_FILES', 30),
        'max_size_mb' => env('CA_LOG_MAX_SIZE_MB', 100),
        'compress' => env('CA_LOG_COMPRESS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Event Dispatch
    |--------------------------------------------------------------------------
    |
    | When enabled, critical and emergency log entries automatically dispatch
    | the CriticalLogRecorded event for notification or alerting purposes.
    |
    */

    'dispatch_critical_events' => env('CA_LOG_DISPATCH_CRITICAL', true),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Configuration for the log query API routes.
    |
    */

    'routes' => [
        'enabled' => env('CA_LOG_ROUTES_ENABLED', true),
        'prefix' => env('CA_LOG_ROUTES_PREFIX', 'api/ca'),
        'middleware' => ['api'],
    ],

];

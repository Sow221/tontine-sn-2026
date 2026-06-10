<?php

use Monolog\Formatter\JsonFormatter;

return [

    /*
    |--------------------------------------------------------------------------
    | Transaction Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file provides structured logging setup for PayTech
    | transactions. Logs are stored in JSON format with correlation IDs
    | for distributed tracing across the application.
    |
    */

    'channel' => 'transactions',

    'path' => storage_path('logs/transactions'),

    'retention_days' => env('LOG_TRANSACTIONS_RETENTION', 30),

    'rotation' => 'daily',

    'formatter' => JsonFormatter::class,

    'formatter_options' => [
        'dateFormat' => 'Y-m-d H:i:s.u',
        'batchMode' => JsonFormatter::BATCH_MODE_JSON,
        'appendNewline' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Fields Configuration
    |--------------------------------------------------------------------------
    |
    | List of field names that should be redacted from logs for security
    | purposes. Any key matching these patterns will be replaced with
    | ***REDACTED*** in log output.
    |
    */

    'sensitive_fields' => [
        'api_key',
        'api_secret',
        'password',
        'token',
        'secret',
        'authorization',
        'credential',
        'pin',
        'otp',
        'card_number',
        'cvv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Levels
    |--------------------------------------------------------------------------
    |
    | Define minimum log levels for different transaction events.
    |
    */

    'levels' => [
        'initiation' => 'info',
        'verification' => 'info',
        'confirmation' => 'info',
        'error' => 'error',
        'webhook' => 'info',
        'status_change' => 'info',
    ],

];

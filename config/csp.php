<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Content Security Policy (CSP)
    | headers to protect against XSS attacks and other security vulnerabilities.
    |
    */

    'enabled' => env('CSP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | CSP Directives
    |--------------------------------------------------------------------------
    |
    | Define the CSP directives for different resource types.
    | Use 'self' for same-origin, specific domains for external resources.
    |
    */

    'directives' => [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'nonce-{nonce}'"],
        'style-src' => ["'self'", "'nonce-{nonce}'"],
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'", 'data:'],
        'connect-src' => ["'self'"],
        'frame-ancestors' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'object-src' => ["'none'"],
        'media-src' => ["'self'"],
        'worker-src' => ["'self'"],
        'manifest-src' => ["'self'"],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | When in development mode, you can add additional directives for
    | development tools like browser extensions, hot reload, etc.
    |
    */

    'development' => [
        'enabled' => env('CSP_DEVELOPMENT_MODE', false),
        'additional_directives' => [
            'script-src' => ["'unsafe-eval'"], // For development tools
            'connect-src' => ["'self'", 'ws:', 'wss:'], // For hot reload
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Only Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, CSP violations will be reported but not blocked.
    | Useful for testing CSP policies without breaking functionality.
    |
    */

    'report_only' => env('CSP_REPORT_ONLY', false),
    'report_uri' => env('CSP_REPORT_URI', '/api/csp-report'),

    /*
    |--------------------------------------------------------------------------
    | Nonce Generation
    |--------------------------------------------------------------------------
    |
    | Configuration for nonce generation and validation.
    |
    */

    'nonce' => [
        'length' => 16, // Bytes
        'encoding' => 'base64',
    ],
];

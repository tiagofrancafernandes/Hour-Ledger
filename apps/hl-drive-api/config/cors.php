<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        '*',
        'api/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'hlcore.com',
        'api.hlcore.com',
        'local.hlcore.com',
        'api.local.hlcore.com',
        ...array_unique(
            array_filter(
                array_map(fn($v) => trim("{$v}"), [
                    env('FRONTEND_URL', 'https://hlcore.com'),
                    ...explode(',', strval(env('CENTRAL_DOMAINS'))),
                    ...explode(',', strval(env('ALLOWED_ORIGINS'))),
                    env('CENTRAL_DOMAIN'),
                    env('SAAS_DOMAIN'),
                    env('CUSTOMER_APP_DOMAIN'),
                    env('BACKOFFICE_DOMAIN'),
                    env('API_DOMAIN'),
                    env('APP_MAIN_DOMAIN'),
                    env('FRONTEND_MAIN_DOMAIN'),
                    '127.0.0.1',
                    'localhost',
                ])
            )
        )
    ],

    // Define your regular expressions here
    'allowed_origins_patterns' => [
        '/^https?:\/\/([a-z0-9-]+\.)?example\.com$/', // Matches example.com and any of its subdomains
        '/^http:\/\/localhost:(3000|5173|8080)$/',    // Matches localhost on ports 3000, 5173, or 8080
        '/^https?:\/\/([a-z0-9-]+\.)?hlcore\.com$/', // Matches hlcore.com and any of its subdomains
        '/^https?:\/\/([a-z0-9-]+\.)?local\.hlcore\.com$/', // Matches local.hlcore.com and any of its subdomains
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Content-Disposition',
        'X-Filename',
        'Content-Type',
        'Response-Type',
        'X-Response-Type',
    ],

    'max_age' => 0,

    'supports_credentials' => true,
];

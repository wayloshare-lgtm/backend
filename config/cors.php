<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your application. CORS allows
    | the Flutter mobile app and web clients to make requests to this API.
    |
    | Paths: Define which routes should have CORS headers applied
    | Methods: HTTP methods allowed (GET, POST, PUT, DELETE, PATCH, OPTIONS)
    | Origins: Domains allowed to make requests to this API
    | Headers: Request headers that are allowed
    | Exposed Headers: Response headers exposed to the client
    | Credentials: Whether to include credentials (cookies, auth headers)
    | Max Age: How long the browser can cache preflight requests (seconds)
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],

    'allowed_origins' => [
        'https://wayloshare.com',
        'https://app.wayloshare.com',
        'https://admin.wayloshare.com',
        'http://localhost:3000',      // Local development (web)
        'http://localhost:8000',      // Local development (API)
        'http://localhost:8080',      // Local development (Flutter web)
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8080',
    ],

    'allowed_origins_patterns' => [
        // Allow any subdomain of wayloshare.com in production
        '#^https://.*\.wayloshare\.com$#',
    ],

    'allowed_headers' => [
        'Accept',
        'Accept-Language',
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-Token',
        'X-API-Key',
        'X-Device-ID',
        'X-Device-Type',
        'X-App-Version',
        'X-Firebase-Token',
    ],

    'exposed_headers' => [
        'Content-Type',
        'X-Total-Count',
        'X-Page-Count',
        'X-Current-Page',
        'X-Per-Page',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400,  // 24 hours - cache preflight requests

    'supports_credentials' => true,  // Allow credentials (cookies, auth headers)
];

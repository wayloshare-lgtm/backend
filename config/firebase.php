<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Admin SDK integration
    |
    */

    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
    ],

    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],

    'messaging' => [
        'enabled' => true,
    ],
];

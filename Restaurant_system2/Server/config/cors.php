<?php

return [

    'paths' => ['api/*', 'storage/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'https://one-hotel-lemon.vercel.app',
        env('APP_FRONTEND_URL', 'https://one-hotel-lemon.vercel.app'),
    ],

    'allowed_origins_patterns' => ['/localhost:\d+/', '/127\.0\.0\.1:\d+/', '/\.vercel\.app$/'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];

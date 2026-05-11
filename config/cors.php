<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',    // Vue dev server
        'http://localhost',         // Thêm dòng này cho WampServer
        'http://127.0.0.1',         // Thêm dòng này để phòng hờ
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,   // ← quan trọng cho Sanctum
];
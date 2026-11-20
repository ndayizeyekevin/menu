<?php

require_once __DIR__ . '/../includes/env.php';

return [
    'host' => env('DB_HOST', 'localhost'),
    'port' => (int) env('DB_PORT', 3306),
    'database' => env('DB_NAME', env('DB_DATABASE', '')),
    'username' => env('DB_USERNAME', ''),
    'password' => env('DB_PASSWORD', ''),
];

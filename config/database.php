<?php

require_once __DIR__ . '/../includes/env.php';

function env_int_database(string $name, int $default): int
{
    $value = env($name);
    return is_string($value) && ctype_digit(trim($value))
        ? (int) trim($value)
        : $default;
}

return [
    'host' => env('DB_HOST', 'localhost'),
    'port' => env_int_database('DB_PORT', 3306),
    'database' => env('DB_DATABASE', ''),
    'username' => env('DB_USERNAME', ''),
    'password' => env('DB_PASSWORD', ''),
];

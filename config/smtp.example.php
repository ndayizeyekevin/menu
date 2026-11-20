<?php

// SMTP settings are loaded from the root .env file.
require_once __DIR__ . '/../includes/env.php';

$smtpPort = env('SMTP_PORT', 587);
$smtpPort = is_string($smtpPort) && ctype_digit(trim($smtpPort))
    ? (int) trim($smtpPort)
    : 587;

return [
    'host' => env('SMTP_HOST', ''),
    'port' => $smtpPort,
    'secure' => env('SMTP_SECURE', 'tls'),
    'username' => env('SMTP_USERNAME', ''),
    'password' => env('SMTP_PASSWORD', ''),
    'from_email' => env('SMTP_FROM_EMAIL', ''),
    'from_name' => env('SMTP_FROM_NAME', 'KABC Hotel'),
    'support_email' => env('SMTP_SUPPORT_EMAIL', ''),
    'support_phone' => env('SMTP_SUPPORT_PHONE', ''),
];

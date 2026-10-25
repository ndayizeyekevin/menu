<?php

require_once __DIR__ . '/../../includes/DBController.php';
require_once __DIR__ . '/../../includes/admin_session.php';

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function adminEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminSetFlash(string $message, string $type = 'success'): void
{
    $_SESSION['admin_flash'] = ['message' => $message, 'type' => $type];
}

function adminConsumeFlash(): ?array
{
    if (empty($_SESSION['admin_flash'])) {
        return null;
    }

    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    return $flash;
}

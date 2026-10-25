<?php
require_once __DIR__ . '/includes/require_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Invalid sign-out request.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Strict',
    ]);
}
session_destroy();

header('Location: index.php');
exit;

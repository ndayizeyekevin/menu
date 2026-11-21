<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_schema.php';

$errorMessage = '';
$resetRequestMessage = '';
$email = '';

try {
    $setupMode = initializeAdminAuthSchema($db);
    if ($setupMode) {
        header('Location: index');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $errorMessage = 'Your session has expired. Please try again.';
        } else {
            $email = isset($_POST['email']) && is_string($_POST['email'])
                ? trim($_POST['email'])
                : '';
            $remoteAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);

            $db->exec(
                'DELETE FROM admin_password_reset_requests
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)'
            );
            $recordRequest = $db->prepare(
                'INSERT INTO admin_password_reset_requests (ip_address) VALUES (:ip_address)'
            );
            $recordRequest->execute([':ip_address' => $remoteAddress]);
            $recentRequests = $db->prepare(
                'SELECT COUNT(*) FROM admin_password_reset_requests
                 WHERE ip_address = :ip_address
                   AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
            );
            $recentRequests->execute([':ip_address' => $remoteAddress]);

            if ((int) $recentRequests->fetchColumn() > 5) {
                $errorMessage = 'Too many reset requests. Please wait 15 minutes and try again.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                $errorMessage = 'Enter a valid account email address.';
            } else {
                $findUser = $db->prepare('SELECT id FROM admin_users WHERE email = :email');
                $findUser->execute([':email' => $email]);
                $user = $findUser->fetch();

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $saveToken = $db->prepare(
                        'UPDATE admin_users
                         SET password_reset_key = :token_hash,
                             reset_key_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                         WHERE id = :id'
                    );
                    $saveToken->execute([
                        ':token_hash' => $tokenHash,
                        ':id' => $user['id'],
                    ]);

                    $serverName = (string) ($_SERVER['SERVER_NAME'] ?? '');
                    if ($serverName !== '' && preg_match('/^[a-zA-Z0-9.-]+$/', $serverName)) {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $port = (int) ($_SERVER['SERVER_PORT'] ?? 0);
                        $portSuffix = $port > 0 && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))
                            ? ':' . $port
                            : '';
                        $basePath = dirname(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/forgot_password.php')));
                        $basePath = $basePath === '\\' || $basePath === '.' ? '' : rtrim(str_replace('\\', '/', $basePath), '/');
                        $resetUrl = $scheme . '://' . $serverName . $portSuffix . $basePath
                            . '/admin/reset_password?token=' . rawurlencode($token);

                        try {
                            require_once __DIR__ . '/../includes/password_reset_mailer.php';
                            sendAdminPasswordResetEmail($email, $resetUrl);
                            error_log('Admin password reset email accepted by the configured mail transport.');
                        } catch (Throwable $e) {
                            $db->prepare(
                                'UPDATE admin_users SET password_reset_key = NULL, reset_key_expiry = NULL
                                 WHERE id = :id AND password_reset_key = :token_hash'
                            )->execute([
                                ':id' => $user['id'],
                                ':token_hash' => $tokenHash,
                            ]);
                            error_log('Admin password reset email could not be sent: ' . $e->getMessage());
                        }
                    } else {
                        $db->prepare(
                            'UPDATE admin_users SET password_reset_key = NULL, reset_key_expiry = NULL WHERE id = :id'
                        )->execute([':id' => $user['id']]);
                        error_log('Admin password reset email could not be sent: invalid server name configuration.');
                    }
                }

                $resetRequestMessage = 'For security, we can’t confirm whether an account uses that address. If it does and the email service accepts delivery, a reset link will arrive. If you don’t receive one, contact support.';
            }
        }
    }
} catch (PDOException $e) {
    error_log('Admin password reset request failed: ' . $e->getMessage());
    http_response_code(500);
    $errorMessage = 'Password reset is temporarily unavailable. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot password | KABC Hotel</title>
    <script src="../assets/js/admin_theme.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/admin.css?v=11">
    <link rel="icon" type="image/png" href="../assets/images/icon.png">
</head>
<body class="login-page">
    <button class="theme-toggle login-theme-toggle" type="button" data-theme-toggle aria-label="Toggle color theme" aria-pressed="false">
        <i class="fa-solid fa-sun theme-toggle-icon" aria-hidden="true"></i>
        <span class="theme-toggle-label">Dark theme</span>
    </button>
    <main class="login-card">
        <a class="login-brand" href="../full_menu" aria-label="KABC Hotel public menu">
            <img src="../assets/images/icon.png" alt="KABC Hotel">
        </a>
        <p class="eyebrow">KABC HOTEL ADMIN</p>
        <h1>Forgot your password?</h1>
        <p class="muted">Enter the email address linked to your admin account. For security, we can’t confirm whether an account uses that address.</p>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert"><?= adminEscape($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($resetRequestMessage !== ''): ?>
            <div class="notice" role="status"><?= adminEscape($resetRequestMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
            <label for="email">Account email</label>
            <input id="email" name="email" type="email" maxlength="190" autocomplete="email" required value="<?= adminEscape($email) ?>">
            <button class="button primary full-width" type="submit">Email reset link</button>
        </form>
        <a class="login-public-link" href="index">Return to sign in</a>
    </main>
    <script src="../assets/js/password_visibility.js?v=1" defer></script>
</body>
</html>

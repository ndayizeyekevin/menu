<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Referrer-Policy: no-referrer');

$token = isset($_POST['token']) && is_string($_POST['token'])
    ? $_POST['token']
    : (isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '');
$validToken = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$errorMessage = $validToken ? '' : 'This reset link is invalid or has expired.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Your session has expired. Please try again.';
    } else {
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
            ? $_POST['confirm_password']
            : '';

        if (!$validToken) {
            $errorMessage = 'This reset link is invalid or has expired.';
        } elseif (strlen($password) < 12) {
            $errorMessage = 'The new password must contain at least 12 characters.';
        } elseif ($password !== $confirmPassword) {
            $errorMessage = 'The new password and confirmation do not match.';
        } else {
            try {
                $db->beginTransaction();
                $findUser = $db->prepare(
                    'SELECT id, username FROM admin_users
                     WHERE password_reset_key = :token_hash AND reset_key_expiry > NOW()
                     LIMIT 1 FOR UPDATE'
                );
                $findUser->execute([':token_hash' => hash('sha256', $token)]);
                $user = $findUser->fetch();

                if ($user) {
                    $update = $db->prepare(
                        'UPDATE admin_users
                         SET password_hash = :password_hash,
                             password_reset_key = NULL,
                             reset_key_expiry = NULL,
                             updated_at = NOW()
                         WHERE id = :id AND password_reset_key = :token_hash
                           AND reset_key_expiry > NOW()'
                    );
                    $update->execute([
                        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        ':id' => $user['id'],
                        ':token_hash' => hash('sha256', $token),
                    ]);

                    if ($update->rowCount() === 1) {
                        $db->commit();
                        $_SESSION['admin_password_reset_notice'] = 'Password reset. Sign in using your new password.';
                        header('Location: index.php');
                        exit;
                    }
                }

                $db->rollBack();
                $errorMessage = 'This reset link is invalid or has expired.';
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Admin password reset failed: ' . $e->getMessage());
                http_response_code(500);
                $errorMessage = 'The password could not be reset. Please request a new link and try again.';
            }
        }
    }
}

if ($validToken && $errorMessage === '') {
    try {
        $checkToken = $db->prepare(
            'SELECT id FROM admin_users
             WHERE password_reset_key = :token_hash AND reset_key_expiry > NOW()'
        );
        $checkToken->execute([':token_hash' => hash('sha256', $token)]);
        $validToken = (bool) $checkToken->fetchColumn();
        if (!$validToken) {
            $errorMessage = 'This reset link is invalid or has expired.';
        }
    } catch (PDOException $e) {
        error_log('Unable to validate admin password reset link: ' . $e->getMessage());
        http_response_code(500);
        $validToken = false;
        $errorMessage = 'The reset link could not be checked. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password | KABC Hotel</title>
    <script src="../assets/js/admin_theme.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/admin.css?v=11">
    <link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body class="login-page">
    <button class="theme-toggle login-theme-toggle" type="button" data-theme-toggle aria-label="Toggle color theme" aria-pressed="false">
        <i class="fa-solid fa-sun theme-toggle-icon" aria-hidden="true"></i>
        <span class="theme-toggle-label">Dark theme</span>
    </button>
    <main class="login-card">
        <a class="login-brand" href="../full_menu" aria-label="KABC Hotel public menu">
            <img src="../img/icon.png" alt="KABC Hotel">
        </a>
        <p class="eyebrow">KABC HOTEL ADMIN</p>
        <h1>Reset your password</h1>
        <p class="muted">Choose a new password with at least 12 characters.</p>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert"><?= adminEscape($errorMessage) ?></div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                <input type="hidden" name="token" value="<?= adminEscape($token) ?>">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                <label for="confirm_password">Confirm new password</label>
                <input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required>
                <button class="button primary full-width" type="submit">Reset password</button>
            </form>
        <?php else: ?>
            <a class="button primary full-width" href="index.php">Return to sign in</a>
        <?php endif; ?>
    </main>
    <script src="../assets/js/password_visibility.js?v=1" defer></script>
</body>
</html>

<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errorMessage = '';
$setupMode = false;
$recoveryNotice = $_SESSION['admin_recovery_notice'] ?? '';
unset($_SESSION['admin_recovery_notice']);

try {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            password_recovery_hash CHAR(64) NULL,
            email VARCHAR(190) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $userColumns = $db->query('SHOW COLUMNS FROM admin_users')->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('password_recovery_hash', $userColumns, true)) {
        $db->exec('ALTER TABLE admin_users ADD password_recovery_hash CHAR(64) NULL AFTER password_hash');
    }
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admin_login_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            username VARCHAR(64) NOT NULL,
            success TINYINT(1) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_login_ip_success_date (ip_address, success, created_at),
            INDEX idx_admin_login_date (created_at),
            CONSTRAINT fk_admin_login_user
                FOREIGN KEY (user_id) REFERENCES admin_users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $setupMode = (int) $db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $errorMessage = 'Your session has expired. Please try again.';
        } else {
            $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : 'login';
            $username = isset($_POST['username']) && is_string($_POST['username'])
                ? trim($_POST['username'])
                : '';
            $password = isset($_POST['password']) && is_string($_POST['password'])
                ? $_POST['password']
                : '';
            $email = isset($_POST['email']) && is_string($_POST['email'])
                ? trim($_POST['email'])
                : '';
            $remoteAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
            $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

            if (!$setupMode && $action === 'reset_password') {
                $recoveryCode = isset($_POST['recovery_code']) && is_string($_POST['recovery_code'])
                    ? strtolower(trim($_POST['recovery_code']))
                    : '';
                $newPassword = isset($_POST['new_password']) && is_string($_POST['new_password'])
                    ? $_POST['new_password']
                    : '';
                $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
                    ? $_POST['confirm_password']
                    : '';
                $failedAttempts = $db->prepare(
                    'SELECT COUNT(*) FROM admin_login_logs
                     WHERE ip_address = :ip_address AND success = 0
                       AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
                );
                $failedAttempts->execute([':ip_address' => $remoteAddress]);
                if ((int) $failedAttempts->fetchColumn() >= 10) {
                    $errorMessage = 'Too many attempts. Please wait 15 minutes and try again.';
                } elseif (
                    !preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)
                    || !preg_match('/^[a-f0-9]{64}$/', $recoveryCode)
                    || strlen($newPassword) < 12
                    || $newPassword !== $confirmPassword
                ) {
                    $errorMessage = 'The recovery details are invalid or the new passwords do not match.';
                } else {
                    $findUser = $db->prepare(
                        'SELECT id, password_recovery_hash FROM admin_users WHERE username = :username'
                    );
                    $findUser->execute([':username' => $username]);
                    $user = $findUser->fetch();
                    $validRecovery = $user
                        && is_string($user['password_recovery_hash'])
                        && hash_equals($user['password_recovery_hash'], hash('sha256', $recoveryCode));
                    $newRecoveryCode = $validRecovery ? bin2hex(random_bytes(32)) : null;
                    if ($validRecovery) {
                        $resetPassword = $db->prepare(
                            'UPDATE admin_users
                             SET password_hash = :password_hash, password_recovery_hash = :recovery_hash
                             WHERE id = :id'
                        );
                        $resetPassword->execute([
                            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                            ':recovery_hash' => hash('sha256', $newRecoveryCode),
                            ':id' => $user['id'],
                        ]);
                    }
                    $logAttempt = $db->prepare(
                        'INSERT INTO admin_login_logs (user_id, username, success, ip_address, user_agent)
                         VALUES (:user_id, :username, :success, :ip_address, :user_agent)'
                    );
                    $logAttempt->execute([
                        ':user_id' => $validRecovery ? $user['id'] : null,
                        ':username' => $username,
                        ':success' => $validRecovery ? 1 : 0,
                        ':ip_address' => $remoteAddress,
                        ':user_agent' => $userAgent,
                    ]);
                    if ($validRecovery) {
                        $_SESSION['admin_recovery_notice'] = $newRecoveryCode;
                        $_SESSION['admin_password_reset_notice'] = 'Password reset. Sign in using your new password.';
                        header('Location: index.php');
                        exit;
                    }

                    $errorMessage = 'The username or recovery code is incorrect.';
                }
            } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)) {
                $errorMessage = 'Use a username of 3–64 letters, numbers, dots, underscores, or hyphens.';
            } elseif ($setupMode && strlen($password) < 12) {
                $errorMessage = 'Choose a password with at least 12 characters.';
            } elseif (!$setupMode && $password === '') {
                $errorMessage = 'Enter your password.';
            } elseif ($setupMode && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMessage = 'Enter a valid email address or leave the field empty.';
            } else {
                if ($setupMode) {
                    $lockAcquired = (int) $db->query("SELECT GET_LOCK('kabc_menu_admin_setup', 5)")->fetchColumn() === 1;
                    if (!$lockAcquired) {
                        $errorMessage = 'Initial admin setup is busy. Please try again.';
                    } else {
                        try {
                            $setupMode = (int) $db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0;
                            if (!$setupMode) {
                                $errorMessage = 'An admin account has already been created. Please sign in.';
                            } else {
                                $db->beginTransaction();
                                $recoveryCode = bin2hex(random_bytes(32));
                                $createUser = $db->prepare(
                                    'INSERT INTO admin_users (username, password_hash, password_recovery_hash, email)
                                     VALUES (:username, :password_hash, :recovery_hash, :email)'
                                );
                                $createUser->execute([
                                    ':username' => $username,
                                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                                    ':recovery_hash' => hash('sha256', $recoveryCode),
                                    ':email' => $email !== '' ? $email : null,
                                ]);
                                $userId = (int) $db->lastInsertId();
                                $logSetup = $db->prepare(
                                    'INSERT INTO admin_login_logs (user_id, username, success, ip_address, user_agent)
                                     VALUES (:user_id, :username, 1, :ip_address, :user_agent)'
                                );
                                $logSetup->execute([
                                    ':user_id' => $userId,
                                    ':username' => $username,
                                    ':ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45),
                                    ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                                ]);
                                $db->commit();
                                $_SESSION['admin_recovery_notice'] = $recoveryCode;
                                $setupMode = false;
                            }
                        } catch (PDOException $e) {
                            if ($db->inTransaction()) {
                                $db->rollBack();
                            }
                            error_log('Initial admin account setup failed: ' . $e->getMessage());
                            $errorMessage = 'The admin account could not be created. Please try again.';
                        } finally {
                            $db->query("SELECT RELEASE_LOCK('kabc_menu_admin_setup')");
                        }
                    }
                } else {
                    $failedAttempts = $db->prepare(
                        'SELECT COUNT(*) FROM admin_login_logs
                         WHERE ip_address = :ip_address AND success = 0
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
                    );
                    $failedAttempts->execute([':ip_address' => $remoteAddress]);

                    if ((int) $failedAttempts->fetchColumn() >= 10) {
                        $errorMessage = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
                    } else {
                        $findUser = $db->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username');
                        $findUser->execute([':username' => $username]);
                        $user = $findUser->fetch();
                        $authenticated = $user && password_verify($password, $user['password_hash']);

                        $logAttempt = $db->prepare(
                            'INSERT INTO admin_login_logs (user_id, username, success, ip_address, user_agent)
                             VALUES (:user_id, :username, :success, :ip_address, :user_agent)'
                        );
                        $logAttempt->execute([
                            ':user_id' => $authenticated ? $user['id'] : null,
                            ':username' => $username,
                            ':success' => $authenticated ? 1 : 0,
                            ':ip_address' => $remoteAddress,
                            ':user_agent' => $userAgent,
                        ]);

                        if ($authenticated) {
                            session_regenerate_id(true);
                            $_SESSION['admin_user_id'] = (int) $user['id'];
                            $_SESSION['admin_username'] = $user['username'];
                            $db->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id')
                                ->execute([':id' => $user['id']]);
                            header('Location: dashboard.php');
                            exit;
                        }

                        $errorMessage = 'The username or password is incorrect.';
                    }
                }

                if ($setupMode === false && isset($userId) && $errorMessage === '') {
                    session_regenerate_id(true);
                    $_SESSION['admin_user_id'] = $userId;
                    $_SESSION['admin_username'] = $username;
                    header('Location: dashboard.php');
                    exit;
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('Admin login initialization failed: ' . $e->getMessage());
    http_response_code(500);
    $errorMessage = 'Admin sign-in is temporarily unavailable. Please try again later.';
}

if (!empty($_SESSION['admin_user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$passwordResetNotice = $_SESSION['admin_password_reset_notice'] ?? '';
unset($_SESSION['admin_password_reset_notice']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $setupMode ? 'Set up admin' : 'Admin sign in' ?> | KABC Hotel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../img/icon.png">
</head>
<body class="login-page">
    <main class="login-card">
        <a class="login-brand" href="../full_menu" aria-label="KABC Hotel public menu">
            <img src="../img/icon.png" alt="KABC Hotel">
        </a>
        <p class="eyebrow">KABC HOTEL ADMIN</p>
        <h1><?= $setupMode ? 'Create your admin account' : 'Welcome back' ?></h1>
        <p class="muted">
            <?= $setupMode
                ? 'This one-time setup creates the first administrator. Choose a strong password and keep it private.'
                : 'Sign in to manage menu items, images, and your admin account.' ?>
        </p>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert"><?= adminEscape($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($passwordResetNotice !== ''): ?>
            <div class="notice success" role="status"><?= adminEscape($passwordResetNotice) ?></div>
        <?php endif; ?>
        <?php if ($recoveryNotice !== ''): ?>
            <div class="notice success recovery-code" role="alert">
                <strong>Save this recovery code now. It will not be shown again.</strong>
                <code><?= adminEscape($recoveryNotice) ?></code>
            </div>
        <?php endif; ?>

        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
            <input type="hidden" name="action" value="<?= $setupMode ? 'setup' : 'login' ?>">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" minlength="3" placeholder="Enter username" maxlength="64" autocomplete="username" required value="<?= adminEscape($_POST['username'] ?? '') ?>">

            <?php if ($setupMode): ?>
                <label for="email">Email <span class="muted">(optional)</span></label>
                <input id="email" name="email" type="email" maxlength="190" placeholder="Enter email" autocomplete="email" value="<?= adminEscape($_POST['email'] ?? '') ?>">
            <?php endif; ?>

            <label for="password"><?= $setupMode ? 'Password (12+ characters)' : 'Password' ?></label>
            <input id="password" name="password" type="password" placeholder="Enter password" minlength="<?= $setupMode ? '12' : '1' ?>" autocomplete="<?= $setupMode ? 'new-password' : 'current-password' ?>" required>

            <button class="button primary full-width" type="submit"><?= $setupMode ? 'Create admin account' : 'Sign in' ?></button>
        </form>
        <?php if (!$setupMode): ?>
            <details class="reset-password">
                <summary>Reset password with recovery code</summary>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <label for="reset_username">Username</label>
                    <input id="reset_username" name="username" type="text" maxlength="64" autocomplete="username" required>
                    <label for="recovery_code">Recovery code</label>
                    <input id="recovery_code" name="recovery_code" type="text" minlength="64" maxlength="64" autocomplete="off" required>
                    <label for="new_password">New password (12+ characters)</label>
                    <input id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password" required>
                    <label for="confirm_password">Confirm new password</label>
                    <input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required>
                    <button class="button primary full-width" type="submit">Reset password</button>
                </form>
            </details>
        <?php endif; ?>
        <a class="login-public-link" href="../full_menu">View public menu</a>
    </main>
</body>
</html>

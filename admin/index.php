<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errorMessage = '';
$setupMode = false;
$resetRequestMessage = '';

try {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            firstName VARCHAR(100) NULL,
            lastName VARCHAR(100) NULL,
            password_hash VARCHAR(255) NOT NULL,
            password_reset_key VARCHAR(255) NULL,
            reset_key_expiry TIMESTAMP NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $userColumns = $db->query('SHOW COLUMNS FROM admin_users')->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ([
        'firstName' => 'VARCHAR(100) NULL',
        'lastName' => 'VARCHAR(100) NULL',
        'password_reset_key' => 'VARCHAR(255) NULL',
        'reset_key_expiry' => 'TIMESTAMP NULL',
        'email' => 'VARCHAR(190) NULL',
    ] as $column => $definition) {
        if (!in_array($column, $userColumns, true)) {
            $db->exec("ALTER TABLE admin_users ADD `$column` $definition");
        }
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
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admin_password_reset_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_password_reset_ip_date (ip_address, created_at)
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
            $firstName = isset($_POST['firstName']) && is_string($_POST['firstName'])
                ? trim($_POST['firstName'])
                : '';
            $lastName = isset($_POST['lastName']) && is_string($_POST['lastName'])
                ? trim($_POST['lastName'])
                : '';
            $remoteAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
            $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

            if (!$setupMode && $action === 'request_password_reset') {
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
                } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                            $basePath = dirname(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')));
                            $basePath = $basePath === '\\' || $basePath === '.' ? '' : rtrim(str_replace('\\', '/', $basePath), '/');
                            $resetUrl = $scheme . '://' . $serverName . $portSuffix . $basePath
                                . '/admin/reset_password?token=' . rawurlencode($token);

                            try {
                                require_once __DIR__ . '/../includes/password_reset_mailer.php';
                                sendAdminPasswordResetEmail($email, $resetUrl);
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

                    $resetRequestMessage = 'If an account uses that email address and email delivery is available, reset instructions will arrive shortly. The link expires in one hour.';
                } else {
                    $errorMessage = 'Enter a valid email address.';
                }
            } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)) {
                $errorMessage = 'Use a username of 3–64 letters, numbers, dots, underscores, or hyphens.';
            } elseif ($setupMode && strlen($password) < 12) {
                $errorMessage = 'Choose a password with at least 12 characters.';
            } elseif (!$setupMode && $password === '') {
                $errorMessage = 'Enter your password.';
            } elseif ($setupMode && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
                $errorMessage = 'Enter a valid email address (maximum 190 characters).';
            } elseif ($setupMode && ($firstName === '' || strlen($firstName) > 100 || $lastName === '' || strlen($lastName) > 100)) {
                $errorMessage = 'Enter your first and last name (maximum 100 characters each).';
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
                                $createUser = $db->prepare(
                                    'INSERT INTO admin_users (username, firstName, lastName, password_hash, email)
                                     VALUES (:username, :first_name, :last_name, :password_hash, :email)'
                                );
                                $createUser->execute([
                                    ':username' => $username,
                                    ':first_name' => $firstName,
                                    ':last_name' => $lastName,
                                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                                    ':email' => $email,
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
        <?php if ($resetRequestMessage !== ''): ?>
            <div class="notice success" role="status"><?= adminEscape($resetRequestMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
            <input type="hidden" name="action" value="<?= $setupMode ? 'setup' : 'login' ?>">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" minlength="3" placeholder="Enter username" maxlength="64" autocomplete="username" required value="<?= adminEscape($_POST['username'] ?? '') ?>">

            <?php if ($setupMode): ?>
                <label for="firstName">First name</label>
                <input id="firstName" name="firstName" type="text" maxlength="100" autocomplete="given-name" required value="<?= adminEscape($_POST['firstName'] ?? '') ?>">
                <label for="lastName">Last name</label>
                <input id="lastName" name="lastName" type="text" maxlength="100" autocomplete="family-name" required value="<?= adminEscape($_POST['lastName'] ?? '') ?>">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="190" placeholder="Enter account email" autocomplete="email" required value="<?= adminEscape($_POST['email'] ?? '') ?>">
            <?php endif; ?>

            <label for="password"><?= $setupMode ? 'Password (12+ characters)' : 'Password' ?></label>
            <input id="password" name="password" type="password" placeholder="Enter password" minlength="<?= $setupMode ? '12' : '1' ?>" autocomplete="<?= $setupMode ? 'new-password' : 'current-password' ?>" required>

            <button class="button primary full-width" type="submit"><?= $setupMode ? 'Create admin account' : 'Sign in' ?></button>
        </form>
        <?php if (!$setupMode): ?>
            <details class="reset-password">
                <summary>Forgot your password?</summary>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                    <input type="hidden" name="action" value="request_password_reset">
                    <label for="reset_email">Account email</label>
                    <input id="reset_email" name="email" type="email" maxlength="190" autocomplete="email" required>
                    <button class="button primary full-width" type="submit">Email reset link</button>
                </form>
            </details>
        <?php endif; ?>
        <a class="login-public-link" href="../full_menu">View public menu</a>
    </main>
    <script src="../assets/js/password_visibility.js?v=1" defer></script>
</body>
</html>

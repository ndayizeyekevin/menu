<?php
require_once __DIR__ . '/includes/require_admin.php';

$errorMessage = '';
$successMessage = '';
$recoveryCode = $_SESSION['admin_recovery_notice'] ?? '';
unset($_SESSION['admin_recovery_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Your session has expired. Please try again.';
    } else {
        $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : 'change_password';
        $currentPassword = isset($_POST['current_password']) && is_string($_POST['current_password'])
            ? $_POST['current_password']
            : '';

        try {
            $stmt = $db->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
            $stmt->execute([':id' => $_SESSION['admin_user_id']]);
            $admin = $stmt->fetch();

            if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
                $errorMessage = 'Your current password is incorrect.';
            } elseif ($action === 'regenerate_recovery') {
                $recoveryCode = bin2hex(random_bytes(32));
                $update = $db->prepare('UPDATE admin_users SET password_recovery_hash = :recovery_hash WHERE id = :id');
                $update->execute([
                    ':recovery_hash' => hash('sha256', $recoveryCode),
                    ':id' => $_SESSION['admin_user_id'],
                ]);
                $_SESSION['admin_recovery_notice'] = $recoveryCode;
                header('Location: change_password.php');
                exit;
            } else {
                $newPassword = isset($_POST['new_password']) && is_string($_POST['new_password'])
                    ? $_POST['new_password']
                    : '';
                $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
                    ? $_POST['confirm_password']
                    : '';
                if (strlen($newPassword) < 12) {
                    $errorMessage = 'The new password must contain at least 12 characters.';
                } elseif ($newPassword !== $confirmPassword) {
                    $errorMessage = 'The new password and confirmation do not match.';
                } else {
                    $update = $db->prepare('UPDATE admin_users SET password_hash = :password_hash WHERE id = :id');
                    $update->execute([
                        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                        ':id' => $_SESSION['admin_user_id'],
                    ]);
                    session_regenerate_id(true);
                    $successMessage = 'Password updated successfully.';
                }
            }
        } catch (PDOException $e) {
            error_log('Unable to update admin password: ' . $e->getMessage());
            http_response_code(500);
            $errorMessage = 'The password could not be updated. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change password | KABC Hotel</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php require __DIR__ . '/includes/header.php'; ?>
        <header class="admin-header">
            <div>
                <p class="eyebrow">ADMIN ACCOUNT</p>
                <h1>Change password</h1>
                <p class="muted">Use a unique password with at least 12 characters.</p>
            </div>
        </header>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert"><?= adminEscape($errorMessage) ?></div>
        <?php elseif ($successMessage !== ''): ?>
            <div class="notice success" role="status"><?= adminEscape($successMessage) ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <form class="admin-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                <input type="hidden" name="action" value="change_password">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" placeholder="Enter current password" autocomplete="current-password" required>

                <label for="new_password">New password</label>
                <input id="new_password" name="new_password" type="password" placeholder="Enter new password" minlength="12" autocomplete="new-password" required>

                <label for="confirm_password">Confirm new password</label>
                <input id="confirm_password" name="confirm_password" type="password" minlength="12" placeholder="Confirm new password" autocomplete="new-password" required>

                <button class="button primary" type="submit">Update password</button>
            </form>
        </section>
        <section class="admin-card recovery-card">
            <h2>Account recovery</h2>
            <p class="muted">If you lose your password, the recovery code lets you reset it from the sign-in page. Generate a new code if you no longer have yours.</p>
            <?php if ($recoveryCode !== ''): ?>
                <div class="notice success recovery-code" role="alert">
                    <strong>Store this new recovery code securely. It will not be shown again.</strong>
                    <code><?= adminEscape($recoveryCode) ?></code>
                </div>
            <?php endif; ?>
            <form class="admin-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                <input type="hidden" name="action" value="regenerate_recovery">
                <label for="recovery_current_password">Current password</label>
                <input id="recovery_current_password" name="current_password" type="password" placeholder="Enter current password" autocomplete="current-password" required>
                <button class="button" type="submit">Generate recovery code</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>

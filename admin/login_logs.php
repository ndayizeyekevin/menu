<?php
require_once __DIR__ . '/includes/require_admin.php';

try {
    $logs = $db->query(
        'SELECT username, success, ip_address, user_agent, created_at
         FROM admin_login_logs ORDER BY created_at DESC, id DESC LIMIT 100'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Unable to load admin login activity: ' . $e->getMessage());
    http_response_code(500);
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login activity | KABC Hotel</title>
    <link rel="icon" type="image/png" href="../img/icon.png">
    <script src="../assets/js/admin_theme.js"></script>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <?php require __DIR__ . '/includes/header.php'; ?>
        <header class="admin-header">
            <div>
                <p class="eyebrow">ADMINISTRATION</p>
                <h1>Login activity</h1>
                <p class="muted">The latest 100 successful and failed admin sign-in attempts.</p>
            </div>
        </header>
        <section class="admin-card table-wrap">
            <table class="admin-table">
                <thead><tr><th>When</th><th>Username</th><th>Result</th><th>IP address</th><th>Browser</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= adminEscape($log['created_at']) ?></td>
                        <td><?= adminEscape($log['username']) ?></td>
                        <td class="<?= $log['success'] ? 'activity-success' : 'activity-failed' ?>"><?= $log['success'] ? 'Success' : 'Failed' ?></td>
                        <td><?= adminEscape($log['ip_address']) ?></td>
                        <td><?= adminEscape($log['user_agent']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?><tr><td colspan="5" class="muted">No login activity recorded.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/includes/require_admin.php';

try {
    $menuCount = (int) $db->query('SELECT COUNT(*) FROM menu')->fetchColumn();
    $imageCount = (int) $db->query("SELECT COUNT(*) FROM menu WHERE image_path IS NOT NULL AND image_path <> ''")->fetchColumn();
    $loginCount = (int) $db->query(
        'SELECT COUNT(*) FROM admin_login_logs WHERE success = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
    )->fetchColumn();
} catch (PDOException $e) {
    error_log('Unable to load admin dashboard data: ' . $e->getMessage());
    http_response_code(500);
    $menuCount = 0;
    $imageCount = 0;
    $loginCount = 0;
    $flash = ['message' => 'Dashboard data could not be loaded.', 'type' => 'error'];
}

$flash = $flash ?? adminConsumeFlash();
$recoveryCode = $_SESSION['admin_recovery_notice'] ?? '';
unset($_SESSION['admin_recovery_notice']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin dashboard | KABC Hotel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <div>
                <p class="eyebrow">ADMINISTRATION</p>
                <h1>Welcome, <?= adminEscape($_SESSION['admin_username']) ?></h1>
                <p class="muted">Manage your menu and account from one place.</p>
            </div>
            <a class="button" href="../full_menu">View public menu</a>
        </header>

        <?php if ($flash): ?>
            <div class="notice <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= adminEscape($flash['message']) ?></div>
        <?php endif; ?>
        <?php if ($recoveryCode !== ''): ?>
            <div class="notice success recovery-code" role="alert">
                <strong>Save this recovery code securely. It will not be shown again.</strong>
                <code><?= adminEscape($recoveryCode) ?></code>
            </div>
        <?php endif; ?>

        <section class="stats-grid" aria-label="Menu summary">
            <article class="admin-card stat-card"><span class="muted">Menu items</span><strong><?= $menuCount ?></strong></article>
            <article class="admin-card stat-card"><span class="muted">Items with images</span><strong><?= $imageCount ?></strong></article>
            <article class="admin-card stat-card"><span class="muted">Successful logins (30 days)</span><strong><?= $loginCount ?></strong></article>
        </section>

        <section class="admin-grid">
            <article class="admin-card">
                <h2>Manage menu</h2>
                <p class="muted">Update item names, descriptions, and prices.</p>
                <a class="button primary" href="menu.php">Open menu management</a>
            </article>
            <article class="admin-card">
                <h2>Upload images</h2>
                <p class="muted">Add or replace photos shown on the public menu.</p>
                <a class="button primary" href="menu_image_upload.php">Upload a menu image</a>
            </article>
        </section>
    </main>
</div>
</body>
</html>

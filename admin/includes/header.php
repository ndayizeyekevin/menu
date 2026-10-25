<header class="admin-topbar">
    <a class="admin-topbar-brand" href="dashboard.php">
        <span>KABC <strong>ADMIN</strong></span>
    </a>
    <div class="admin-topbar-actions">
        <span class="admin-user"><?= adminEscape($_SESSION['admin_username'] ?? 'Admin') ?></span>
        <a class="admin-topbar-public" href="../full_menu">View public menu</a>
    </div>
</header>

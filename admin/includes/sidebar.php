<aside class="admin-sidebar">
    <div class="admin-sidebar-head">
        <a class="admin-brand" href="dashboard.php" aria-label="KABC Hotel admin home">
            <img src="../img/icon.png" alt="">
            <span class="admin-brand-copy">KABC Hotel <strong>ADMIN</strong></span>
        </a>
        <button
            class="admin-sidebar-toggle"
            type="button"
            aria-label="Open admin navigation"
            aria-expanded="false"
            aria-controls="adminSidebarPanel"
        >
            <span class="sidebar-toggle-icon" aria-hidden="true">☰</span>
            <span>Menu</span>
        </button>
    </div>
    <div class="admin-sidebar-panel" id="adminSidebarPanel">
        <p class="sidebar-section-label">Workspace</p>
        <nav aria-label="Admin navigation">
            <a href="dashboard.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? ' class="active"' : '' ?>><span class="nav-icon" aria-hidden="true">▦</span>Dashboard</a>
            <a href="menu.php"<?= in_array(basename($_SERVER['SCRIPT_NAME']), ['menu.php', 'menu_edit.php'], true) ? ' class="active"' : '' ?>><span class="nav-icon" aria-hidden="true">☷</span>Menu management</a>
            <a href="menu_image_upload.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'menu_image_upload.php' ? ' class="active"' : '' ?>><span class="nav-icon" aria-hidden="true">▧</span>Upload images</a>
            <a href="login_logs.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'login_logs.php' ? ' class="active"' : '' ?>><span class="nav-icon" aria-hidden="true">◷</span>Login activity</a>
        </nav>
        <div class="admin-sidebar-footer">
            <a class="sidebar-public-link" href="../full_menu"><span aria-hidden="true">↗</span>View public site</a>
        </div>
    </div>
</aside>
<script src="../assets/js/admin_sidebar.js?v=2"></script>

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
            <i class="fa-solid fa-bars sidebar-toggle-icon" aria-hidden="true"></i>
        </button>
    </div>
    <div class="admin-sidebar-panel" id="adminSidebarPanel">
        <p class="sidebar-section-label">Workspace</p>
        <nav aria-label="Admin navigation">
            <a href="dashboard.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? ' class="active"' : '' ?>><i class="fa-solid fa-gauge nav-icon" aria-hidden="true"></i>Dashboard</a>
            <a href="menu.php"<?= in_array(basename($_SERVER['SCRIPT_NAME']), ['menu.php', 'menu_edit.php'], true) ? ' class="active"' : '' ?>><i class="fa-solid fa-utensils nav-icon" aria-hidden="true"></i>Menu management</a>
            <a href="menu_image_upload.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'menu_image_upload.php' ? ' class="active"' : '' ?>><i class="fa-solid fa-image nav-icon" aria-hidden="true"></i>Upload images</a>
            <a href="login_logs.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'login_logs.php' ? ' class="active"' : '' ?>><i class="fa-solid fa-clock-rotate-left nav-icon" aria-hidden="true"></i>Login activity</a>
        </nav>
        <div class="admin-sidebar-footer">
            <a class="sidebar-public-link" href="../full_menu"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>View public site</a>
            <small class="fa-attribution">Icons by <a href="https://fontawesome.com" target="_blank" rel="noopener">Font Awesome</a>, CC BY 4.0.</small>
        </div>
    </div>
</aside>
<script src="../assets/js/admin_sidebar.js?v=4"></script>

<aside class="admin-sidebar">
    <a class="admin-brand" href="dashboard.php">KABC <span>ADMIN</span></a>
    <nav aria-label="Admin navigation">
        <a href="dashboard.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? ' class="active"' : '' ?>>Dashboard</a>
        <a href="menu.php"<?= in_array(basename($_SERVER['SCRIPT_NAME']), ['menu.php', 'menu_edit.php'], true) ? ' class="active"' : '' ?>>Menu management</a>
        <a href="menu_image_upload.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'menu_image_upload.php' ? ' class="active"' : '' ?>>Upload images</a>
        <a href="login_logs.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'login_logs.php' ? ' class="active"' : '' ?>>Login activity</a>
        <a href="change_password.php"<?= basename($_SERVER['SCRIPT_NAME']) === 'change_password.php' ? ' class="active"' : '' ?>>Change password</a>
    </nav>
    <form class="admin-logout" action="logout" method="post">
        <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
        <button type="submit">Sign out</button>
    </form>
</aside>

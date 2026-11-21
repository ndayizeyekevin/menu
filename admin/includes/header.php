<header class="admin-topbar">
    <strong class="topbar-title">
        <?php
        $topbarTitles = [
            'dashboard.php' => 'Dashboard',
            'menu.php' => 'Menu Management',
            'menu_edit.php' => 'Menu Management',
            'menu_image_upload.php' => 'Menu Management',
            'login_logs.php' => 'Login Activity',
            'change_password.php' => 'Account Settings',
        ];
        echo adminEscape($topbarTitles[basename($_SERVER['SCRIPT_NAME'])] ?? 'KABC Hotel Admin');
        ?>
    </strong>
    <div class="admin-topbar-actions">
        <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle color theme" aria-pressed="false">
            <i class="fa-solid fa-sun theme-toggle-icon" aria-hidden="true"></i>
            <span class="theme-toggle-label">Dark theme</span>
        </button>
        <div class="admin-account">
            <div class="admin-account-trigger" tabindex="0" role="group" aria-label="Account menu for <?= adminEscape($_SESSION['admin_username'] ?? 'Admin') ?>">
                <span class="user-avatar" aria-hidden="true"><?= adminEscape(strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1))) ?></span>
                <span class="admin-account-name"><?= adminEscape($_SESSION['admin_username'] ?? 'Admin') ?></span>
            </div>
            <div class="admin-account-menu">
                <a href="change_password.php"><i class="fa-solid fa-gear" aria-hidden="true"></i>Settings</a>
                <form action="<?= adminEscape(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/logout') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                    <button type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>Sign out</button>
                </form>
            </div>
        </div>
    </div>
</header>

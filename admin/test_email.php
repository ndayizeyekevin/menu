<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/smtp_config.php';

$recipient = 'ndayizeyekevin6@gmail.com';
$isAdmin = !empty($_SESSION['admin_user_id']);
$config = [];
$errorMessage = $isAdmin ? '' : 'Sign in to the admin area before testing email settings.';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        $errorMessage = 'Sign in to the admin area before testing email settings.';
    } elseif (!adminVerifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Your session has expired. Reload the page and try again.';
    } else {
        try {
            $config = loadSmtpConfig();
            if ($config['transport'] !== 'smtp') {
                throw new RuntimeException('SMTP test requires SMTP_TRANSPORT=smtp.');
            }

            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $config['host'];
            $mailer->Port = $config['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $config['username'];
            $mailer->Password = $config['password'];
            $mailer->SMTPSecure = $config['secure'] === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->SMTPDebug = 0;
            $mailer->Timeout = 15;
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->setFrom($config['from_email'], $config['from_name']);
            $mailer->addAddress($recipient);
            $mailer->Subject = 'KABC Hotel SMTP configuration test';
            $mailer->Body = "This is a test email from the KABC Hotel application.\n"
                . "If you received it, the configured SMTP server accepted the message.";
            $mailer->AltBody = $mailer->Body;

            if (!$mailer->send()) {
                throw new RuntimeException('The SMTP server did not accept the test email.');
            }

            error_log('SMTP configuration test email accepted by the SMTP server.');
            $successMessage = 'The SMTP server accepted the test email. Check the recipient inbox and spam folder.';
        } catch (Throwable $e) {
            $logMessage = $e->getMessage();
            foreach (['username', 'password'] as $credentialKey) {
                if (isset($config[$credentialKey]) && is_string($config[$credentialKey]) && $config[$credentialKey] !== '') {
                    $logMessage = str_replace($config[$credentialKey], '[redacted]', $logMessage);
                }
            }
            error_log('SMTP configuration test failed: ' . $logMessage);
            $errorMessage = 'The test email could not be sent. Check the server error log for details; credentials are not shown on this page.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMTP | KABC Hotel</title>
    <script src="../assets/js/admin_theme.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/admin.css?v=12">
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
        <h1>Test SMTP settings</h1>
        <p class="muted">This sends one test message to <?= adminEscape($recipient) ?> using the SMTP settings configured for this application. It does not display your credentials.</p>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert"><?= adminEscape($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($successMessage !== ''): ?>
            <div class="notice info" role="status"><?= adminEscape($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <form method="post" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= adminEscape(adminCsrfToken()) ?>">
                <button class="button primary full-width" type="submit">Send test email</button>
            </form>
            <a class="login-public-link" href="dashboard">Return to dashboard</a>
        <?php else: ?>
            <a class="login-public-link" href="index">Sign in to the admin area</a>
        <?php endif; ?>
    </main>
</body>
</html>

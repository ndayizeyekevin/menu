<?php

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function sendAdminPasswordResetEmail(string $recipient, string $resetUrl): void
{
    $configPath = __DIR__ . '/../config/smtp.php';
    if (
        !is_file($configPath)
        || !is_readable($configPath)
    ) {
        throw new RuntimeException('SMTP configuration file is missing or unreadable.');
    }

    $config = require $configPath;
    if (
        !is_array($config)
        || !isset($config['host'], $config['port'], $config['secure'], $config['username'], $config['password'])
        || !is_string($config['host']) || $config['host'] === ''
        || !is_int($config['port']) || $config['port'] < 1 || $config['port'] > 65535
        || !is_string($config['secure']) || !in_array(strtolower($config['secure']), ['tls', 'ssl'], true)
        || !is_string($config['username']) || $config['username'] === ''
        || !is_string($config['password']) || $config['password'] === ''
    ) {
        throw new RuntimeException('SMTP configuration is incomplete or invalid.');
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $config['host'];
    $mailer->Port = $config['port'];
    $mailer->SMTPAuth = true;
    $mailer->Username = $config['username'];
    $mailer->Password = $config['password'];
    $mailer->SMTPSecure = strtolower($config['secure']) === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Timeout = 15;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($config['username'], 'KABC Hotel');
    $mailer->addAddress($recipient);
    $mailer->isHTML(true);
    $mailer->Subject = 'Reset your KABC Hotel admin password';
    $escapedUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mailer->Body = '<p>A password reset was requested for your KABC Hotel admin account.</p>'
        . '<p><a href="' . $escapedUrl . '">Reset your password</a></p>'
        . '<p>This link expires in one hour. If you did not request this, you can ignore this email.</p>';
    $mailer->AltBody = "A password reset was requested for your KABC Hotel admin account.\n\n"
        . "Reset your password using this link (expires in one hour):\n" . $resetUrl
        . "\n\nIf you did not request this, you can ignore this email.";

    try {
        $mailer->send();
    } catch (MailException $e) {
        throw new RuntimeException('Unable to send admin password reset email.', 0, $e);
    }
}

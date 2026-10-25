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

    $fromEmail = $config['from_email'] ?? $config['username'];
    $fromName = $config['from_name'] ?? 'KABC Hotel';
    if (
        !is_string($fromEmail) || filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false
        || !is_string($fromName) || trim($fromName) === ''
    ) {
        throw new RuntimeException('SMTP sender email or name is invalid.');
    }

    $supportEmail = $config['support_email'] ?? '';
    $supportPhone = $config['support_phone'] ?? '';
    $supportPhoneLink = is_string($supportPhone)
        ? preg_replace('/[^0-9+]/', '', $supportPhone)
        : '';
    if (
        !is_string($supportEmail) || filter_var($supportEmail, FILTER_VALIDATE_EMAIL) === false
        || !is_string($supportPhone) || !is_string($supportPhoneLink)
        || preg_match('/^\+[0-9]{8,15}$/', $supportPhoneLink) !== 1
    ) {
        throw new RuntimeException('SMTP support contact details are invalid.');
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
    $mailer->setFrom($fromEmail, $fromName);
    $mailer->addAddress($recipient);
    $mailer->isHTML(true);
    $mailer->Subject = 'Reset your KABC Hotel admin password';
    $escapedUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mailer->Body = '<p>A password reset was requested for your KABC Hotel admin account.</p>'
        . '<p><a href="' . $escapedUrl . '">Reset your password</a></p>'
        . '<p>This link expires in one hour. If you did not request this, you can ignore this email.</p>'
        . '<hr><p><small>This email was sent from an unattended mailbox. Please do not reply to this email.</small></p>'
        . '<p><small>Powered by <a href="https://nusk.rw">NUSK Tech Solutions Group</a></small></p>';
    $mailer->AltBody = "A password reset was requested for your KABC Hotel admin account.\n\n"
        . "Reset your password using this link (expires in one hour):\n" . $resetUrl
        . "\n\nIf you did not request this, you can ignore this email."
        . "\n\nThis email was sent from an unattended mailbox. Please do not reply to this email."
        . "\n\nContact NUSK Support:\nEmail: " . $supportEmail . "\nPhone: " . $supportPhone
        . "\n\nPowered by NUSK Tech Solutions Group (https://nusk.rw)";
    $escapedSupportEmail = htmlspecialchars($supportEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $escapedSupportPhone = htmlspecialchars($supportPhone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mailer->Body .= '<p><small>Contact NUSK Support: <a href="mailto:' . $escapedSupportEmail . '">'
        . $escapedSupportEmail . '</a> | <a href="tel:' . $supportPhoneLink . '">'
        . $escapedSupportPhone . '</a></small></p>';

    try {
        $mailer->send();
    } catch (MailException $e) {
        throw new RuntimeException('Unable to send admin password reset email.', 0, $e);
    }
}

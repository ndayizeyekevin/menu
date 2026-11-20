<?php

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/smtp_config.php';

function sendAdminPasswordResetEmail(string $recipient, string $resetUrl): void
{
    $config = loadSmtpConfig(true);
    $supportEmail = $config['support_email'];
    $supportPhone = $config['support_phone'];
    $supportPhoneLink = preg_replace('/[^0-9+]/', '', $supportPhone);

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
    $mailer->Timeout = 15;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($config['from_email'], $config['from_name']);
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
        if (!$mailer->send()) {
            throw new RuntimeException('SMTP server did not accept the password reset email.');
        }
    } catch (MailException $e) {
        throw new RuntimeException('Unable to send admin password reset email.', 0, $e);
    }
}

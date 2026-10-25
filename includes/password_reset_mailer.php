<?php

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function sendAdminPasswordResetEmail(string $recipient, string $resetUrl): void
{
    $smtpHost = getenv('SMTP_HOST');
    $smtpPort = getenv('SMTP_PORT');
    $smtpSecure = strtolower((string) getenv('SMTP_SECURE'));
    $smtpUsername = getenv('SMTP_USERNAME');
    $smtpPassword = getenv('SMTP_PASSWORD');

    if (
        !is_string($smtpHost) || $smtpHost === ''
        || !is_string($smtpPort) || filter_var($smtpPort, FILTER_VALIDATE_INT) === false
        || !in_array($smtpSecure, ['tls', 'ssl'], true)
        || !is_string($smtpUsername) || $smtpUsername === ''
        || !is_string($smtpPassword) || $smtpPassword === ''
    ) {
        throw new RuntimeException('SMTP configuration is incomplete.');
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $smtpHost;
    $mailer->Port = (int) $smtpPort;
    $mailer->SMTPAuth = true;
    $mailer->Username = $smtpUsername;
    $mailer->Password = $smtpPassword;
    $mailer->SMTPSecure = $smtpSecure === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Timeout = 15;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($smtpUsername, 'KABC Hotel');
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

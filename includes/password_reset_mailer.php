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
    $logoPath = __DIR__ . '/../assets/images/email-logo.png';
    if (!is_file($logoPath) || !is_readable($logoPath)) {
        throw new RuntimeException('Email logo is missing or unreadable.');
    }
    $mailer->addEmbeddedImage(
        $logoPath,
        'nusk-tech-logo',
        'email-logo.png',
        PHPMailer::ENCODING_BASE64,
        'image/png',
        'inline'
    );
    $mailer->isHTML(true);
    $mailer->Subject = 'Reset your KABC Hotel admin password';
    $escapedUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mailer->Body = '<!doctype html><html lang="en"><body style="margin:0;padding:24px;background:#f3f4f8;font-family:Arial,sans-serif;color:#252536;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;">'
        . '<tr><td style="padding:28px 32px;text-align:center;border-bottom:1px solid #e8e8ef;">'
        . '<img src="cid:nusk-tech-logo" alt="NUSK Tech Solutions Group" width="260" style="display:block;width:100%;max-width:260px;height:auto;margin:0 auto;">'
        . '</td></tr><tr><td style="padding:32px;">'
        . '<h1 style="margin:0 0 16px;font-size:24px;">Reset your admin password</h1>'
        . '<p style="margin:0 0 22px;line-height:1.6;">A password reset was requested for your KABC Hotel admin account.</p>'
        . '<p style="margin:0 0 22px;"><a href="' . $escapedUrl . '" style="display:inline-block;padding:13px 22px;background:#4038a8;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;">Reset your password</a></p>'
        . '<p style="margin:0 0 12px;line-height:1.6;">This link expires in one hour. If you did not request this, you can ignore this email.</p>'
        . '<p style="margin:22px 0 0;padding-top:18px;border-top:1px solid #e8e8ef;color:#666;font-size:13px;line-height:1.6;">'
        . 'This email was sent from an unattended mailbox. Please do not reply.<br>'
        . 'NUSK Support: <a href="mailto:' . htmlspecialchars($supportEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="color:#4038a8;">'
        . htmlspecialchars($supportEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a> | <a href="tel:' . htmlspecialchars($supportPhoneLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '" style="color:#4038a8;">' . htmlspecialchars($supportPhone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a><br>'
        . 'Powered by <a href="https://nusk.rw" style="color:#4038a8;">NUSK Tech Solutions Group</a></p>'
        . '</td></tr></table></body></html>';
    $mailer->AltBody = "A password reset was requested for your KABC Hotel admin account.\n\n"
        . "Reset your password using this link (expires in one hour):\n" . $resetUrl
        . "\n\nIf you did not request this, you can ignore this email."
        . "\n\nThis email was sent from an unattended mailbox. Please do not reply to this email."
        . "\n\nContact NUSK Support:\nEmail: " . $supportEmail . "\nPhone: " . $supportPhone
        . "\n\nPowered by NUSK Tech Solutions Group (https://nusk.rw)";

    try {
        if (!$mailer->send()) {
            throw new RuntimeException('SMTP server did not accept the password reset email.');
        }
    } catch (MailException $e) {
        throw new RuntimeException('Unable to send admin password reset email.', 0, $e);
    }
}

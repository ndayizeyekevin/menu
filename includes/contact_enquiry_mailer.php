<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/smtp_config.php';

function sendKabcRoomEnquiry(array $enquiry): void
{
    $config = loadSmtpConfig();

    $mailer = new PHPMailer(true);
    if ($config['transport'] === 'sendmail') {
        $mailer->isSendmail();
        $mailer->Sendmail = $config['sendmail_path'];
    } else {
        $mailer->isSMTP();
        $mailer->Host = $config['host'];
        $mailer->Port = $config['port'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $config['username'];
        $mailer->Password = $config['password'];
        $mailer->SMTPSecure = $config['secure'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mailer->Timeout = 15;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($config['from_email'], $config['from_name']);
    $mailer->addAddress('reservations@kabchotel.rw', 'KABC Hotel Reservations');
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
    if ($enquiry['email'] !== '') {
        $mailer->addReplyTo($enquiry['email'], $enquiry['name']);
    }
    $mailer->Subject = 'KABC Hotel room enquiry';
    $mailer->isHTML(true);
    $mailer->Body = '<!doctype html><html lang="en"><body style="margin:0;padding:24px;background:#f3f4f8;font-family:Arial,sans-serif;color:#252536;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#fff;border-radius:12px;">'
        . '<tr><td style="padding:28px 32px;text-align:center;border-bottom:1px solid #e8e8ef;">'
        . '<img src="cid:nusk-tech-logo" alt="NUSK Tech Solutions Group" width="260" style="display:block;width:100%;max-width:260px;height:auto;margin:0 auto;">'
        . '</td></tr><tr><td style="padding:28px 32px;">'
        . '<h1 style="margin:0 0 8px;font-size:22px;">New room enquiry</h1>'
        . '<p style="margin:0 0 22px;color:#666;">A new enquiry was submitted through the KABC Hotel website.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="border-collapse:collapse;">'
        . '<tr><td style="width:145px;color:#666;"><strong>Name</strong></td><td>' . htmlspecialchars($enquiry['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="color:#666;"><strong>Email</strong></td><td>' . htmlspecialchars($enquiry['email'] !== '' ? $enquiry['email'] : 'Not provided', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="color:#666;"><strong>Phone</strong></td><td>' . htmlspecialchars($enquiry['phone'] !== '' ? $enquiry['phone'] : 'Not provided', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="color:#666;"><strong>Room preference</strong></td><td>' . htmlspecialchars($enquiry['room'] !== '' ? $enquiry['room'] : 'Not specified', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="color:#666;"><strong>Arrival</strong></td><td>' . htmlspecialchars($enquiry['arrival'] !== '' ? $enquiry['arrival'] : 'Not specified', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '<tr><td style="color:#666;"><strong>Departure</strong></td><td>' . htmlspecialchars($enquiry['departure'] !== '' ? $enquiry['departure'] : 'Not specified', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
        . '</table><h2 style="margin:24px 0 8px;font-size:17px;">Message</h2>'
        . '<p style="margin:0;line-height:1.6;white-space:pre-wrap;">'
        . htmlspecialchars($enquiry['message'] !== '' ? $enquiry['message'] : 'No additional message.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</p></td></tr></table></body></html>';
    $mailer->AltBody = implode("\n", [
        'A new room enquiry was submitted through the KABC Hotel website.',
        '',
        'Name: ' . $enquiry['name'],
        'Email: ' . ($enquiry['email'] !== '' ? $enquiry['email'] : 'Not provided'),
        'Phone: ' . ($enquiry['phone'] !== '' ? $enquiry['phone'] : 'Not provided'),
        'Room preference: ' . ($enquiry['room'] !== '' ? $enquiry['room'] : 'Not specified'),
        'Arrival: ' . ($enquiry['arrival'] !== '' ? $enquiry['arrival'] : 'Not specified'),
        'Departure: ' . ($enquiry['departure'] !== '' ? $enquiry['departure'] : 'Not specified'),
        '',
        'Message:',
        $enquiry['message'] !== '' ? $enquiry['message'] : 'No additional message.',
    ]);
    if (!$mailer->send()) {
        throw new RuntimeException('SMTP server did not accept the room enquiry email.');
    }
}

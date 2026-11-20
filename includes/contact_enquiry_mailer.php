<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/smtp_config.php';

function sendKabcRoomEnquiry(array $enquiry): void
{
    $config = loadSmtpConfig();

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
    $mailer->addAddress('reservations@kabchotel.rw', 'KABC Hotel Reservations');
    if ($enquiry['email'] !== '') {
        $mailer->addReplyTo($enquiry['email'], $enquiry['name']);
    }
    $mailer->Subject = 'KABC Hotel room enquiry';
    $mailer->isHTML(false);
    $mailer->Body = implode("\n", [
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

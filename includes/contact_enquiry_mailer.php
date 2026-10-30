<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function sendKabcRoomEnquiry(array $enquiry): void
{
    $configPath = __DIR__ . '/../config/smtp.php';
    if (!is_file($configPath) || !is_readable($configPath)) {
        throw new RuntimeException('SMTP configuration is not available.');
    }

    $config = require $configPath;
    if (
        !is_array($config)
        || !isset($config['host'], $config['port'], $config['secure'], $config['username'], $config['password'])
        || !is_string($config['host']) || trim($config['host']) === ''
        || !is_int($config['port']) || $config['port'] < 1 || $config['port'] > 65535
        || !is_string($config['secure']) || !in_array(strtolower($config['secure']), ['tls', 'ssl'], true)
        || !is_string($config['username']) || trim($config['username']) === ''
        || !is_string($config['password']) || trim($config['password']) === ''
    ) {
        throw new RuntimeException('SMTP configuration is invalid.');
    }

    $fromEmail = $config['from_email'] ?? $config['username'];
    $fromName = $config['from_name'] ?? 'KABC Hotel';
    if (!is_string($fromEmail) || filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false
        || !is_string($fromName) || trim($fromName) === '') {
        throw new RuntimeException('SMTP sender configuration is invalid.');
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
    $mailer->send();
}

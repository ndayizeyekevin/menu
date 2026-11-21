<?php

function loadSmtpConfig(bool $requireSupportContact = false): array
{
    $configPath = __DIR__ . '/../config/smtp.php';
    if (!is_file($configPath)) {
        $configPath = __DIR__ . '/../config/smtp.example.php';
    }

    if (!is_file($configPath) || !is_readable($configPath)) {
        throw new RuntimeException('Neither config/smtp.php nor config/smtp.example.php is available and readable.');
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('SMTP configuration is invalid.');
    }

    $transport = strtolower((string) ($config['transport'] ?? 'smtp'));
    if ($transport === 'sendmail') {
        $sendmailPath = $config['sendmail_path'] ?? 'sendmail -t -i';
        if (!is_string($sendmailPath) || trim($sendmailPath) === '') {
            throw new RuntimeException('Sendmail configuration is invalid.');
        }
        $config['transport'] = 'sendmail';
        $config['sendmail_path'] = trim($sendmailPath);
    } else {
        $transport = 'smtp';
        if (
            !isset($config['host'], $config['port'], $config['secure'], $config['username'], $config['password'])
            || !is_string($config['host']) || trim($config['host']) === ''
            || preg_match('/[\s\x00-\x1F\x7F]/', $config['host']) === 1
            || !is_int($config['port']) || $config['port'] < 1 || $config['port'] > 65535
            || !is_string($config['secure']) || !in_array(strtolower($config['secure']), ['tls', 'ssl'], true)
            || !is_string($config['username']) || trim($config['username']) === ''
            || !is_string($config['password']) || trim($config['password']) === ''
        ) {
            throw new RuntimeException('SMTP connection configuration is invalid.');
        }
        $config['secure'] = strtolower($config['secure']);
        $config['transport'] = 'smtp';
    }

    $config['from_email'] = $config['from_email'] ?? ($config['username'] ?? '');
    $config['from_name'] = $config['from_name'] ?? 'KABC Hotel';
    if (
        !is_string($config['from_email'])
        || filter_var($config['from_email'], FILTER_VALIDATE_EMAIL) === false
        || !is_string($config['from_name'])
        || trim($config['from_name']) === ''
    ) {
        throw new RuntimeException('SMTP sender configuration is invalid.');
    }

    if ($requireSupportContact) {
        $supportEmail = $config['support_email'] ?? '';
        $supportPhone = $config['support_phone'] ?? '';
        $supportPhoneLink = is_string($supportPhone)
            ? preg_replace('/[^0-9+]/', '', $supportPhone)
            : '';
        if (
            !is_string($supportEmail)
            || filter_var($supportEmail, FILTER_VALIDATE_EMAIL) === false
            || !is_string($supportPhone)
            || !is_string($supportPhoneLink)
            || preg_match('/^\+[0-9]{8,15}$/', $supportPhoneLink) !== 1
        ) {
            throw new RuntimeException('SMTP support contact configuration is invalid.');
        }
    }

    return $config;
}

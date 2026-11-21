<?php

header_remove('X-Powered-By');

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = '/' . ltrim((string) $uri, '/');
$root = __DIR__;

$privatePaths = [
    '/.env',
    '/.git',
    '/config/',
    '/includes/',
    '/vendor/',
    '/admin/includes/',
];
foreach ($privatePaths as $privatePath) {
    if ($path === rtrim($privatePath, '/') || str_starts_with($path, $privatePath)) {
        http_response_code(403);
        require $root . '/403.php';
        return true;
    }

    if (str_starts_with($path, '/uploads/')) {
        $storageConfig = require $root . '/config/storage.php';
        $uploadDir = realpath($storageConfig['upload_dir']);
        $relativeName = ltrim(substr($path, strlen('/uploads')), '/');
        $uploadFile = $uploadDir !== false
            ? realpath($uploadDir . DIRECTORY_SEPARATOR . $relativeName)
            : false;
        if (
            $uploadFile !== false
            && is_file($uploadFile)
            && str_starts_with(str_replace('\\', '/', $uploadFile), str_replace('\\', '/', rtrim($uploadDir, '/\\')) . '/')
        ) {
            $extension = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
            header('Content-Length: ' . filesize($uploadFile));
            readfile($uploadFile);
            return true;
        }
        http_response_code(404);
        require $root . '/404.php';
        return true;
    }
}

$file = realpath($root . $path);
if ($file !== false && is_file($file)) {
    $relative = str_replace('\\', '/', substr($file, strlen($root)));
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (str_starts_with($relative, '/.') || in_array($relative, ['/composer.json', '/composer.lock', '/README.md'], true)) {
        http_response_code(403);
        require $root . '/403.php';
        return true;
    }
    if ($extension === 'php') {
        require $file;
        return true;
    }

    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ico' => 'image/x-icon',
    ];
    $mime = $mimeTypes[$extension] ?? null;
    if ($mime !== null) {
        header('Content-Type: ' . $mime);
    }
    return false;
}

if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

$phpPath = $root . $path;
if (!str_ends_with($phpPath, '.php')) {
    $phpPath .= '.php';
}
$phpFile = realpath($phpPath);
if ($phpFile !== false && is_file($phpFile)) {
    require $phpFile;
    return true;
}

http_response_code(404);
require $root . '/404.php';
return true;

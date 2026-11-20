<?php

require_once __DIR__ . '/../includes/env.php';

$defaultUploadDir = __DIR__ . '/../uploads';
$uploadDir = env('UPLOAD_DIR', $defaultUploadDir);
$uploadDir = is_string($uploadDir) && trim($uploadDir) !== ''
    ? trim($uploadDir)
    : $defaultUploadDir;

return [
    'upload_dir' => $uploadDir,
    'upload_url_path' => env('UPLOAD_URL_PATH', 'uploads'),
];

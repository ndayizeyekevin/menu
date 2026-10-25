<?php
// Set timezone
date_default_timezone_set('Africa/Kigali');

// Display all errors (for development only)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

try {
    $databaseConfig = require __DIR__ . '/../config/database.php';

    $db = new PDO(
        "mysql:host={$databaseConfig['host']};port={$databaseConfig['port']};dbname={$databaseConfig['database']};charset=utf8mb4",
        $databaseConfig['username'],
        $databaseConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // ✅ ADD TIMEZONE SETTING
    $db->exec("SET time_zone = '+02:00'");

    $conn = $db;
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Please try again later.');
}

$db;
$conn;
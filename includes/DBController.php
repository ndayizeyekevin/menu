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
} catch (pdoexception $e) {
	die("fatal error: connection failed! " . $e->getmessage());
}

$db;
$conn;
<?php

function initializeAdminAuthSchema(PDO $db): bool
{
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            firstName VARCHAR(100) NULL,
            lastName VARCHAR(100) NULL,
            password_hash VARCHAR(255) NOT NULL,
            password_reset_key VARCHAR(255) NULL,
            reset_key_expiry TIMESTAMP NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $userColumns = $db->query('SHOW COLUMNS FROM admin_users')->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ([
        'firstName' => 'VARCHAR(100) NULL',
        'lastName' => 'VARCHAR(100) NULL',
        'password_reset_key' => 'VARCHAR(255) NULL',
        'reset_key_expiry' => 'TIMESTAMP NULL',
        'email' => 'VARCHAR(190) NULL',
    ] as $column => $definition) {
        if (!in_array($column, $userColumns, true)) {
            $db->exec("ALTER TABLE admin_users ADD `$column` $definition");
        }
    }
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admin_login_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            username VARCHAR(64) NOT NULL,
            success TINYINT(1) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_login_ip_success_date (ip_address, success, created_at),
            INDEX idx_admin_login_date (created_at),
            CONSTRAINT fk_admin_login_user
                FOREIGN KEY (user_id) REFERENCES admin_users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $db->exec(
        "CREATE TABLE IF NOT EXISTS admin_password_reset_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_password_reset_ip_date (ip_address, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    return (int) $db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 0;
}

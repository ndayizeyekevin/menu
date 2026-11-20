<?php

header_remove('X-Powered-By');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $databaseSessionHandler = null;
    try {
        $databaseConfig = require __DIR__ . '/../config/database.php';
        if (
            is_array($databaseConfig)
            && is_string($databaseConfig['host'] ?? null)
            && trim((string) $databaseConfig['host']) !== ''
            && is_string($databaseConfig['database'] ?? null)
            && trim((string) $databaseConfig['database']) !== ''
        ) {
            $databaseOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $databaseOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
            $databaseSessionDb = new PDO(
                "mysql:host={$databaseConfig['host']};port={$databaseConfig['port']};dbname={$databaseConfig['database']};charset=utf8mb4",
                $databaseConfig['username'],
                $databaseConfig['password'],
                $databaseOptions
            );
            $databaseSessionDb->exec(
                'CREATE TABLE IF NOT EXISTS app_sessions (
                    id VARCHAR(128) CHARACTER SET ascii NOT NULL PRIMARY KEY,
                    data MEDIUMBLOB NOT NULL,
                    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_app_sessions_last_activity (last_activity)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            $databaseSessionHandler = new class($databaseSessionDb) implements SessionHandlerInterface {
                private PDO $db;

                public function __construct(PDO $db)
                {
                    $this->db = $db;
                }

                public function open(string $path, string $name): bool
                {
                    return true;
                }

                public function close(): bool
                {
                    return true;
                }

                public function read(string $id)
                {
                    $statement = $this->db->prepare('SELECT data FROM app_sessions WHERE id = :id');
                    $statement->execute([':id' => $id]);
                    $row = $statement->fetch();
                    if (!$row) {
                        return '';
                    }
                    $data = $row['data'];
                    return is_string($data) ? $data : '';
                }

                public function write(string $id, string $data): bool
                {
                    $statement = $this->db->prepare(
                        'INSERT INTO app_sessions (id, data, last_activity)
                         VALUES (:id, :data, NOW())
                         ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = NOW()'
                    );
                    return $statement->execute([
                        ':id' => $id,
                        ':data' => $data,
                    ]);
                }

                public function destroy(string $id): bool
                {
                    $statement = $this->db->prepare('DELETE FROM app_sessions WHERE id = :id');
                    return $statement->execute([':id' => $id]);
                }

                public function gc(int $maxLifetime)
                {
                    $statement = $this->db->prepare(
                        'DELETE FROM app_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL :seconds SECOND)'
                    );
                    $statement->bindValue(':seconds', $maxLifetime, PDO::PARAM_INT);
                    $statement->execute();
                    return $statement->rowCount();
                }
            };
        }
    } catch (Throwable $e) {
        error_log('Database-backed sessions unavailable; using PHP session storage: ' . $e->getMessage());
    }

    if ($databaseSessionHandler !== null) {
        session_set_save_handler($databaseSessionHandler, true);
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Strict',
        'path' => '/',
    ]);
    session_start();
}

function adminCsrfToken(): string
{
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function adminVerifyCsrfToken($token): bool
{
    return is_string($token)
        && isset($_SESSION['admin_csrf_token'])
        && hash_equals($_SESSION['admin_csrf_token'], $token);
}

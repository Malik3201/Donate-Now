<?php
declare(strict_types=1);

/**
 * MySQL PDO singleton. Credentials from .env: DB_HOST, DB_NAME, DB_USER, DB_PASS.
 * Most pages call db() after requiring this file (directly or via auth_check.php).
 */

require_once __DIR__ . '/app.php';

/** @return PDO Shared connection for the current request */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_value('DB_HOST', 'localhost');
    $name = env_value('DB_NAME', 'local_donation_connector');
    $user = env_value('DB_USER', 'root');
    $pass = env_value('DB_PASS', '');
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, (string) $user, (string) $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        exit('Database connection failed. Check configuration.');
    }

    return $pdo;
}

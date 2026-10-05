<?php

declare(strict_types=1);

/**
 * Hotel Management System
 *
 * Database Configuration
 *
 * File:
 * backend/config/database.php
 *
 * Database:
 * MySQL / MariaDB
 */

// ---------------------------------------------------------
// Prevent Direct Access
// ---------------------------------------------------------

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}


// ---------------------------------------------------------
// Load Environment Variables
// ---------------------------------------------------------

/**
 * Loads a simple .env file if it exists.
 *
 * Example .env:
 *
 * DB_HOST=127.0.0.1
 * DB_PORT=3306
 * DB_NAME=hotel_management
 * DB_USER=root
 * DB_PASSWORD=
 * DB_CHARSET=utf8mb4
 */
$envFile = dirname(BASE_PATH) . '/.env';

if (file_exists($envFile)) {
    $lines = file(
        $envFile,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    if ($lines !== false) {
        foreach ($lines as $line) {

            $line = trim($line);

            // Ignore empty lines and comments.
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Support optional shell-style export syntax.
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            // Ignore malformed lines.
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            // Strip inline comments from unquoted values.
            $value = preg_replace('/\s+#.*$/', '', $value) ?? $value;
            $value = preg_replace('/\s+;.*$/', '', $value) ?? $value;

            // Remove optional surrounding quotes.
            if (
                strlen($value) >= 2 &&
                (
                    ($value[0] === '"' && $value[-1] === '"') ||
                    ($value[0] === "'" && $value[-1] === "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}


// ---------------------------------------------------------
// Environment Helper
// ---------------------------------------------------------

if (!function_exists('env')) {

    /**
     * Get an environment variable.
     */
    function env(
        string $key,
        mixed $default = null
    ): mixed {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return $value;
    }
}


// ---------------------------------------------------------
// Database Configuration
// ---------------------------------------------------------

$dbHost = (string) env(
    'DB_HOST',
    '127.0.0.1'
);

$dbPort = (int) env(
    'DB_PORT',
    3306
);

$dbName = (string) env(
    'DB_NAME',
    'hotel_management'
);

$dbUser = (string) env(
    'DB_USER',
    'root'
);

$dbPassword = (string) env(
    'DB_PASSWORD',
    ''
);

$dbCharset = (string) env(
    'DB_CHARSET',
    'utf8mb4'
);


// ---------------------------------------------------------
// DSN
// ---------------------------------------------------------

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $dbHost,
    $dbPort,
    $dbName,
    $dbCharset
);


// ---------------------------------------------------------
// PDO Options
// ---------------------------------------------------------

$pdoOptions = [

    // Throw exceptions instead of silently failing.
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

    // Return database rows as associative arrays.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // Use native prepared statements.
    PDO::ATTR_EMULATE_PREPARES => false,

    // Keep persistent connections disabled by default.
    PDO::ATTR_PERSISTENT => false,

    // Automatically free cursors when appropriate.
    PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
];


// ---------------------------------------------------------
// Database Connection
// ---------------------------------------------------------

try {

    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPassword,
        $pdoOptions
    );

} catch (PDOException $exception) {

    /**
     * Never expose database credentials or connection details
     * to the client.
     *
     * Log the actual exception on the server and return a
     * generic error.
     */

    $logDirectory = BASE_PATH . '/storage/logs';

    if (!is_dir($logDirectory)) {
        @mkdir(
            $logDirectory,
            0755,
            true
        );
    }

    $logFile = $logDirectory . '/database.log';

    $logMessage = sprintf(
        "[%s] Database connection failed: %s%s",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        PHP_EOL
    );

    @file_put_contents(
        $logFile,
        $logMessage,
        FILE_APPEND | LOCK_EX
    );

    http_response_code(500);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        [
            'success' => false,
            'message' => 'Database connection failed.',
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


// ---------------------------------------------------------
// Database Helper
// ---------------------------------------------------------

if (!function_exists('db')) {

    /**
     * Return the shared PDO connection.
     */
    function db(): PDO
    {
        global $pdo;

        return $pdo;
    }
}

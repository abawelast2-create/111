<?php
// =============================================================
// tests/bootstrap.php - PHPUnit Bootstrap
// =============================================================

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'testing');

// Load autoloader
require_once APP_ROOT . '/src/Core/Autoloader.php';
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App', APP_ROOT . '/src');

// Load .env if present
$envFile = APP_ROOT . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        putenv(trim($k) . '=' . trim($v));
    }
}

// Configure database
\App\Core\Database::configure([
    'host'    => getenv('DB_HOST') ?: '127.0.0.1',
    'name'    => getenv('DB_NAME') ?: 'attendance_system',
    'user'    => getenv('DB_USER') ?: 'root',
    'pass'    => getenv('DB_PASS') ?: '',
]);

// Minimal config for testing
date_default_timezone_set('Asia/Riyadh');

// Helper functions used across the app
if (!function_exists('db')) {
    function db(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $name = getenv('DB_NAME') ?: 'attendance_test';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $pdo = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        }
        return $pdo;
    }
}

if (!function_exists('getSystemSetting')) {
    function getSystemSetting(string $key, string $default = ''): string {
        return $default;
    }
}

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken(): string {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(string $token): bool {
        return true; // Skip in tests
    }
}

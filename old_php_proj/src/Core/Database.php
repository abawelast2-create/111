<?php
// =============================================================
// src/Core/Database.php - اتصال قاعدة البيانات (مُحسَّن)
// =============================================================

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = self::$config['host'] ?? DB_HOST;
            $name = self::$config['name'] ?? DB_NAME;
            $user = self::$config['user'] ?? DB_USER;
            $pass = self::$config['pass'] ?? DB_PASS;
            $charset = self::$config['charset'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci; SET time_zone = '+03:00'",
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'خطأ في الاتصال بقاعدة البيانات'
                ], JSON_UNESCAPED_UNICODE));
            }
        }
        return self::$instance;
    }

    /**
     * إعادة تعيين الاتصال (للاختبارات)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}

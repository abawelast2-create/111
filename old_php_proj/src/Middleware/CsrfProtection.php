<?php
// =============================================================
// src/Middleware/CsrfProtection.php - حماية CSRF محسنة
// =============================================================

namespace App\Middleware;

class CsrfProtection
{
    /**
     * إنشاء رمز CSRF
     */
    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * التحقق من رمز CSRF (مع تدوير بعد النجاح)
     */
    public static function verifyToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }

        // تدوير التوكن (One-Time Token)
        unset($_SESSION['csrf_token']);
        return true;
    }

    /**
     * إنشاء حقل HTML مخفي
     */
    public static function field(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * التحقق من طلب POST
     */
    public static function validateRequest(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return self::verifyToken($token);
    }

    /**
     * إنشاء Meta tag للـ AJAX
     */
    public static function metaTag(): string
    {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}

<?php
// =============================================================
// src/Core/Controller.php - وحدة التحكم الأساسية
// =============================================================

namespace App\Core;

abstract class Controller
{
    /**
     * عرض قالب (View)
     */
    protected function view(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        include $viewPath;
    }

    /**
     * رد JSON
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * إعادة التوجيه
     */
    protected function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    /**
     * الحصول على مدخلات POST المنظفة
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        if (is_string($value)) {
            return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    /**
     * الحصول على مدخلات بدون تنظيف (للحالات الخاصة كالمحتوى النصي)
     */
    protected function rawInput(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * التحقق من أن الطلب POST
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * التحقق من CSRF
     */
    protected function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return verifyCsrfToken($token);
    }
}

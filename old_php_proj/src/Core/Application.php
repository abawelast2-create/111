<?php
// =============================================================
// src/Core/Application.php - نواة التطبيق
// =============================================================

namespace App\Core;

class Application
{
    private static ?Application $instance = null;
    private Container $container;
    private Router $router;
    private string $basePath;

    public function __construct(string $basePath)
    {
        self::$instance = $this;
        $this->basePath = rtrim($basePath, '/');
        $this->container = Container::getInstance();
        $this->router = new Router();

        $this->registerCoreServices();
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * تسجيل الخدمات الأساسية
     */
    private function registerCoreServices(): void
    {
        $this->container->instance('app', $this);
        $this->container->instance('router', $this->router);

        // Database
        $this->container->singleton('db', function () {
            return Database::getInstance();
        });

        // Cache
        $this->container->singleton(\App\Services\CacheService::class, function () {
            return new \App\Services\CacheService($this->basePath . '/storage/cache');
        });

        // Auth
        $this->container->singleton(\App\Services\AuthService::class, function () {
            return new \App\Services\AuthService();
        });

        // Attendance
        $this->container->singleton(\App\Services\AttendanceService::class, function () {
            return new \App\Services\AttendanceService();
        });

        // Geofence
        $this->container->singleton(\App\Services\GeofenceService::class, function () {
            return new \App\Services\GeofenceService();
        });

        // Export
        $this->container->singleton(\App\Services\ExportService::class, function () {
            return new \App\Services\ExportService();
        });

        // Rate Limiter
        $this->container->singleton(\App\Middleware\RateLimiter::class, function () {
            return new \App\Middleware\RateLimiter();
        });

        // Session Timeout
        $this->container->singleton(\App\Middleware\SessionTimeout::class, function () {
            return new \App\Middleware\SessionTimeout(30);
        });
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * حل خدمة من الحاوية
     */
    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }

    /**
     * تشغيل التطبيق
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // حساب URI النسبي
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        // إزالة base path
        $basePath = rtrim($scriptName, '/');
        if ($basePath && str_starts_with($requestUri, $basePath)) {
            $uri = substr($requestUri, strlen($basePath));
        } else {
            $uri = $requestUri;
        }

        $uri = strtok($uri, '?') ?: '/';

        $this->router->dispatch($method, $uri);
    }
}

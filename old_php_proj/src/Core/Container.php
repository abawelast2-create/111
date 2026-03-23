<?php
// =============================================================
// src/Core/Container.php - حاوية حقن التبعيات (DI Container)
// =============================================================

namespace App\Core;

use Closure;
use RuntimeException;

class Container
{
    private static ?Container $instance = null;
    private array $bindings = [];
    private array $instances = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * تسجيل خدمة (factory)
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * تسجيل خدمة كمفرد (singleton)
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        };
    }

    /**
     * حل خدمة من الحاوية
     */
    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        throw new RuntimeException("Service not found: {$abstract}");
    }

    /**
     * تسجيل instance جاهز
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * هل الخدمة مسجلة؟
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    private function __construct() {}
    private function __clone() {}
}

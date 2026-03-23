<?php
// =============================================================
// src/Services/CacheService.php - نظام التخزين المؤقت
// =============================================================
// يدعم File Cache (للاستضافة المشتركة) مع واجهة جاهزة لـ Redis
// =============================================================

namespace App\Services;

class CacheService
{
    private string $cacheDir;
    private static ?CacheService $instance = null;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0700, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * الحصول على قيمة من الكاش
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getPath($key);
        if (!file_exists($file)) return $default;

        $data = @file_get_contents($file);
        if ($data === false) return $default;

        $entry = @unserialize($data);
        if ($entry === false) return $default;

        // هل انتهت الصلاحية؟
        if ($entry['expires_at'] > 0 && $entry['expires_at'] < time()) {
            @unlink($file);
            return $default;
        }

        return $entry['value'];
    }

    /**
     * تخزين قيمة في الكاش
     * @param int $ttl مدة الصلاحية بالثواني (0 = لا تنتهي)
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $file = $this->getPath($key);
        $entry = [
            'value' => $value,
            'expires_at' => $ttl === 0 ? 0 : time() + $ttl,
            'created_at' => time(),
        ];

        return @file_put_contents($file, serialize($entry), LOCK_EX) !== false;
    }

    /**
     * الحصول أو الحساب والتخزين
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * حذف من الكاش
     */
    public function forget(string $key): bool
    {
        $file = $this->getPath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * حذف مجموعة بنمط (prefix)
     */
    public function forgetByPrefix(string $prefix): int
    {
        $pattern = $this->cacheDir . '/' . $this->sanitizeKey($prefix) . '*';
        $count = 0;
        foreach (glob($pattern) as $file) {
            if (@unlink($file)) $count++;
        }
        return $count;
    }

    /**
     * مسح كل الكاش
     */
    public function flush(): int
    {
        $count = 0;
        foreach (glob($this->cacheDir . '/*.cache') as $file) {
            if (@unlink($file)) $count++;
        }
        return $count;
    }

    /**
     * تنظيف الملفات المنتهية
     */
    public function gc(): int
    {
        $count = 0;
        $now = time();
        foreach (glob($this->cacheDir . '/*.cache') as $file) {
            $data = @file_get_contents($file);
            if ($data === false) continue;
            $entry = @unserialize($data);
            if ($entry !== false && $entry['expires_at'] > 0 && $entry['expires_at'] < $now) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }

    private function getPath(string $key): string
    {
        return $this->cacheDir . '/' . $this->sanitizeKey($key) . '.cache';
    }

    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $key);
    }
}

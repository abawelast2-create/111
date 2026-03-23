<?php
// =============================================================
// src/Core/Lang.php - نظام الترجمة (i18n)
// =============================================================

namespace App\Core;

class Lang
{
    private static string $locale = 'ar';
    private static array $translations = [];
    private static string $langDir = '';

    /**
     * تهيئة نظام الترجمة
     */
    public static function init(string $langDir, string $locale = 'ar'): void
    {
        self::$langDir = rtrim($langDir, '/');
        self::setLocale($locale);
    }

    /**
     * تعيين اللغة الحالية
     */
    public static function setLocale(string $locale): void
    {
        $validLocales = ['ar', 'en'];
        if (!in_array($locale, $validLocales, true)) {
            $locale = 'ar';
        }
        self::$locale = $locale;
        self::$translations = [];
    }

    /**
     * الحصول على اللغة الحالية
     */
    public static function getLocale(): string
    {
        return self::$locale;
    }

    /**
     * هل الاتجاه من اليمين لليسار؟
     */
    public static function isRtl(): bool
    {
        return self::$locale === 'ar';
    }

    /**
     * الحصول على اتجاه النص
     */
    public static function getDir(): string
    {
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    /**
     * ترجمة نص
     * @param string $key المفتاح (مثل: 'auth.login_success')
     * @param array $replace قيم الاستبدال {:name}
     */
    public static function get(string $key, array $replace = []): string
    {
        $parts = explode('.', $key, 2);
        $file = $parts[0];
        $itemKey = $parts[1] ?? $key;

        // تحميل ملف الترجمة إذا لم يكن محملاً
        if (!isset(self::$translations[$file])) {
            self::loadFile($file);
        }

        $text = self::$translations[$file][$itemKey] ?? $key;

        // استبدال المتغيرات
        foreach ($replace as $search => $val) {
            $text = str_replace(":{$search}", (string)$val, $text);
        }

        return $text;
    }

    /**
     * تحميل ملف ترجمة
     */
    private static function loadFile(string $file): void
    {
        $path = self::$langDir . '/' . self::$locale . '/' . $file . '.php';
        if (file_exists($path)) {
            self::$translations[$file] = require $path;
        } else {
            self::$translations[$file] = [];
        }
    }
}

/**
 * اختصار عالمي للترجمة
 */
function __t(string $key, array $replace = []): string
{
    return Lang::get($key, $replace);
}

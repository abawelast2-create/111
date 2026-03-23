<?php
// =============================================================
// config/bootstrap.php - تهيئة التطبيق (v4.0)
// =============================================================
// يُحمَّل بالإضافة إلى includes/config.php للتوافق العكسي
// =============================================================

// ================== التحميل التلقائي PSR-4 ==================
$autoloaderFile = dirname(__DIR__) . '/src/Core/Autoloader.php';
if (!file_exists($autoloaderFile)) {
    return; // Skip v4 bootstrap if src files not deployed
}
require_once $autoloaderFile;

use App\Core\Autoloader;
use App\Core\Lang;

Autoloader::register();
Autoloader::addNamespace('App', dirname(__DIR__) . '/src');

// ================== نظام الترجمة ==================
$locale = 'ar';
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['locale'])) {
    $locale = $_SESSION['locale'];
} elseif (!empty($_COOKIE['locale'])) {
    $locale = $_COOKIE['locale'];
}
Lang::init(dirname(__DIR__) . '/lang', $locale);

// ================== التوافق العكسي ==================
// الدوال القديمة لا تزال متاحة عبر includes/functions.php
// النماذج والخدمات الجديدة متاحة عبر App\* namespace

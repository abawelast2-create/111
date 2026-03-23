<?php
// الصفحة الرئيسية - تحويل إلى لوحة التحكم
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit;
}
header('Location: ' . SITE_URL . '/admin/login.php'); exit;

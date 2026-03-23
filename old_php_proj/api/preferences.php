<?php
// =============================================================
// api/preferences.php - حفظ تفضيلات المستخدم (v4.0)
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (empty($_SESSION['admin_id'])) {
    jsonResponse(['success' => false, 'message' => 'غير مصرح'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$key = $input['key'] ?? '';
$value = $input['value'] ?? '';

$allowedKeys = ['dark_mode', 'language', 'sidebar_collapsed', 'notifications_enabled'];
if (!in_array($key, $allowedKeys, true)) {
    jsonResponse(['success' => false, 'message' => 'مفتاح غير صالح'], 400);
}

$adminId = (int)$_SESSION['admin_id'];
$safeValue = htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');

try {
    $stmt = db()->prepare("
        INSERT INTO user_preferences (admin_id, pref_key, pref_value)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE pref_value = VALUES(pref_value)
    ");
    $stmt->execute([$adminId, $key, $safeValue]);

    // Set cookie for dark mode (so it loads before JS)
    if ($key === 'dark_mode') {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('attendance_theme', $value === '1' ? 'dark' : 'light', [
            'expires' => time() + 31536000, // 1 year
            'path' => '/',
            'secure' => $secure,
            'httponly' => false, // JS needs to read this
            'samesite' => 'Strict',
        ]);
    }

    jsonResponse(['success' => true]);
} catch (Exception $e) {
    // Table might not exist yet — silently handle
    jsonResponse(['success' => false, 'message' => 'خطأ في الحفظ'], 500);
}

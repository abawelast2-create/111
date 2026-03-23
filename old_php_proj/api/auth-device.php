<?php
// =============================================================
// api/auth-device.php — مصادقة الموظف عبر بصمة الجهاز
// إذا كان الجهاز مربوطاً بموظف → يُرجع بيانات الموظف مباشرة
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Rate Limiting: 20 طلب/دقيقة لكل IP
if (isRateLimited(20, 60, 'auth_device')) { rateLimitResponse(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'طريقة طلب غير مسموحة'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonResponse(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
}

$fingerprint = trim($body['fingerprint'] ?? '');

if (empty($fingerprint) || strlen($fingerprint) < 32) {
    jsonResponse(['success' => false, 'message' => 'بصمة الجهاز غير صالحة'], 400);
}

// البحث عن موظف مربوط بهذا الجهاز (ربط صارم أو مراقبة)
$stmt = db()->prepare("
    SELECT id, name, unique_token, pin_changed_at, created_at
    FROM employees 
    WHERE device_fingerprint = ? 
      AND device_bind_mode IN (1, 2) 
      AND is_active = 1 
      AND deleted_at IS NULL 
    LIMIT 1
");
$stmt->execute([$fingerprint]);
$employee = $stmt->fetch();

if (!$employee) {
    jsonResponse(['success' => false, 'message' => 'الجهاز غير مربوط بأي موظف']);
}

jsonResponse([
    'success'        => true,
    'bound'          => true,
    'token'          => $employee['unique_token'],
    'employee_name'  => $employee['name'],
    'employee_id'    => $employee['id'],
    'pin_changed_at' => $employee['pin_changed_at'] ?? $employee['created_at'],
]);

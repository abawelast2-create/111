<?php
// =============================================================
// api/auth-pin.php — مصادقة الموظف عبر PIN
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

// Rate Limiting: 10 طلب/دقيقة لكل IP (أقل لأن هذا مصادقة)
if (isRateLimited(10, 60, 'auth_pin')) { rateLimitResponse(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'طريقة طلب غير مسموحة'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonResponse(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
}

$pin = trim($body['pin'] ?? '');
$fingerprint = trim($body['fingerprint'] ?? '');

if (empty($pin) || !preg_match('/^\d{4}$/', $pin)) {
    jsonResponse(['success' => false, 'message' => 'أدخل رقم PIN صحيح من 4 أرقام'], 400);
}

$employee = getEmployeeByPin($pin);
if (!$employee) {
    jsonResponse(['success' => false, 'message' => 'رقم PIN غير صحيح أو الموظف غير مفعّل'], 403);
}

// ── إذا تم إرسال بصمة الجهاز: تحقق من الربط ──
if (!empty($fingerprint) && strlen($fingerprint) >= 32) {
    // هل هذا الجهاز مربوط بموظف آخر (ربط صارم)؟
    $boundStmt = db()->prepare("
        SELECT id, name, unique_token, pin_changed_at, created_at
        FROM employees 
        WHERE device_fingerprint = ? 
          AND device_bind_mode = 1 
          AND id != ? 
          AND is_active = 1 
          AND deleted_at IS NULL 
        LIMIT 1
    ");
    $boundStmt->execute([$fingerprint, $employee['id']]);
    $boundEmployee = $boundStmt->fetch();

    if ($boundEmployee) {
        // الجهاز مربوط بموظف آخر ← أعد توجيهه للموظف الصحيح
        jsonResponse([
            'success'        => true,
            'redirected'     => true,
            'token'          => $boundEmployee['unique_token'],
            'employee_name'  => $boundEmployee['name'],
            'employee_id'    => $boundEmployee['id'],
            'pin_changed_at' => $boundEmployee['pin_changed_at'] ?? $boundEmployee['created_at'],
            'message'        => 'هذا الجهاز مربوط بحساب ' . $boundEmployee['name'],
        ]);
    }
}

jsonResponse([
    'success'        => true,
    'token'          => $employee['unique_token'],
    'employee_name'  => $employee['name'],
    'employee_id'    => $employee['id'],
    'pin_changed_at' => $employee['pin_changed_at'] ?? $employee['created_at'],
]);

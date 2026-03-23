<?php
// =============================================================
// api/check-out.php - API تسجيل الانصراف
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');

// Rate Limiting: 30 طلب/دقيقة لكل IP
if (isRateLimited(30, 60, 'checkout')) { rateLimitResponse(); }
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'طريقة طلب غير مسموحة'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonResponse(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
}

$token    = trim($body['token']    ?? '');
$lat      = (float) ($body['latitude']  ?? 0);
$lon      = (float) ($body['longitude'] ?? 0);
$accuracy = (float) ($body['accuracy']  ?? 0);

if (empty($token)) {
    jsonResponse(['success' => false, 'message' => 'الرمز المميز مطلوب'], 400);
}
if ($lat === 0.0 && $lon === 0.0) {
    jsonResponse(['success' => false, 'message' => 'بيانات الموقع غير صالحة'], 400);
}

$employee = getEmployeeByToken($token);
if (!$employee) {
    jsonResponse(['success' => false, 'message' => 'رمز غير صالح أو الموظف غير مفعّل'], 403);
}

// التحقق من أن الموظف سجّل دخولاً اليوم أولاً
$stmt = db()->prepare("
    SELECT id FROM attendances
    WHERE employee_id = ? AND type = 'in' AND attendance_date = CURDATE()
    LIMIT 1
");
$stmt->execute([$employee['id']]);
if (!$stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'لم يتم تسجيل الدخول اليوم. سجّل دخولاً أولاً.']);
}

// الانصراف متاح في أي وقت بعد تسجيل الحضور

// التحقق من النطاق الجغرافي (باستخدام فرع الموظف إن وجد)
$geoCheck = isWithinGeofence($lat, $lon, $employee['branch_id'] ?? null);
if (!$geoCheck['allowed']) {
    jsonResponse([
        'success'  => false,
        'message'  => $geoCheck['message'],
        'distance' => $geoCheck['distance']
    ]);
}

// تسجيل الانصراف
$result = recordAttendance($employee['id'], 'out', $lat, $lon, $accuracy);

jsonResponse(array_merge($result, [
    'employee_name' => $employee['name'],
    'timestamp'     => date('Y-m-d H:i:s'),
    'distance'      => $geoCheck['distance'] ?? 0
]));

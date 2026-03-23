<?php
// =============================================================
// api/check-in.php - API تسجيل الدخول
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');

// Rate Limiting: 30 طلب/دقيقة لكل IP
if (isRateLimited(30, 60, 'checkin')) { rateLimitResponse(); }
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

// قراءة جسم الطلب JSON
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonResponse(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
}

$token    = trim($body['token']    ?? '');
$lat      = (float) ($body['latitude']  ?? 0);
$lon      = (float) ($body['longitude'] ?? 0);
$accuracy = (float) ($body['accuracy']  ?? 0);

// التحقق من البيانات
if (empty($token)) {
    jsonResponse(['success' => false, 'message' => 'الرمز المميز مطلوب'], 400);
}
if ($lat === 0.0 && $lon === 0.0) {
    jsonResponse(['success' => false, 'message' => 'بيانات الموقع غير صالحة'], 400);
}

// التحقق من صحة الـ token
$employee = getEmployeeByToken($token);
if (!$employee) {
    jsonResponse(['success' => false, 'message' => 'رمز غير صالح أو الموظف غير مفعّل'], 403);
}

// H2: التحقق من أن التسجيل ضمن نافذة وقت الدوام المسموحة (حسب الفرع)
$schedule       = getBranchSchedule($employee['branch_id'] ?? null);
$ciStart        = $schedule['check_in_start_time'];
$ciEnd          = $schedule['check_in_end_time'];
$nowTime        = date('H:i');
// Handle midnight crossing (e.g. ciEnd < ciStart)
if ($ciEnd < $ciStart) {
    $outsideWindow = ($nowTime < $ciStart && $nowTime > $ciEnd);
} else {
    $outsideWindow = ($nowTime < $ciStart || $nowTime > $ciEnd);
}
if ($outsideWindow) {
    jsonResponse([
        'success' => false,
        'message' => "وقت تسجيل الدخول المسموح به: {$ciStart} - {$ciEnd}. الوقت الحالي: {$nowTime}"
    ], 200);
}

// التحقق من النطاق الجغرافي (باستخدام فرع الموظف إن وجد)
$geoCheck = isWithinGeofence($lat, $lon, $employee['branch_id'] ?? null);
if (!$geoCheck['allowed']) {
    jsonResponse([
        'success'  => false,
        'message'  => $geoCheck['message'],
        'distance' => $geoCheck['distance']
    ], 200);
}

// تسجيل الدخول
$result = recordAttendance($employee['id'], 'in', $lat, $lon, $accuracy);

jsonResponse(array_merge($result, [
    'employee_name' => $employee['name'],
    'timestamp'     => date('Y-m-d H:i:s'),
    'distance'      => $geoCheck['distance'] ?? 0
]));

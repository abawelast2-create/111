<?php
// =============================================================
// api/ot.php - API تسجيل الدوام الإضافي
// (renamed from overtime.php to bypass CDN 404 cache - C3 fix)
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');

// Rate Limiting: 20 طلب/دقيقة لكل IP
if (isRateLimited(20, 60, 'overtime')) { rateLimitResponse(); }
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

// قراءة البيانات
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

// التحقق من الموظف
$employee = getEmployeeByToken($token);
if (!$employee) {
    jsonResponse(['success' => false, 'message' => 'رمز غير صالح'], 403);
}

// التحقق من تفعيل الدوام الإضافي (حسب الفرع)
$schedule = getBranchSchedule($employee['branch_id'] ?? null);
if (!$schedule['allow_overtime']) {
    jsonResponse(['success' => false, 'message' => 'الدوام الإضافي غير مفعّل لهذا الفرع'], 403);
}

// التحقق من النطاق الجغرافي (حسب الفرع)
$geoCheck = isWithinGeofence($lat, $lon, $employee['branch_id'] ?? null);
if (!$geoCheck['allowed']) {
    jsonResponse([
        'success'  => false,
        'message'  => $geoCheck['message'],
        'distance' => $geoCheck['distance']
    ], 200);
}

// التحقق من أن الموظف سجل انصراف اليوم
$stmt = db()->prepare("
    SELECT id FROM attendances
    WHERE employee_id = ? AND type = 'out' AND attendance_date = CURDATE()
    LIMIT 1
");
$stmt->execute([$employee['id']]);
if (!$stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'يجب تسجيل الانصراف أولاً قبل بدء الدوام الإضافي'], 400);
}

// التحقق من عدم وجود session دوام إضافي مفتوحة
$stmt = db()->prepare("
    SELECT id FROM attendances
    WHERE employee_id = ? 
      AND type = 'overtime-start' 
      AND attendance_date = CURDATE()
      AND NOT EXISTS (
          SELECT 1 FROM attendances end_record
          WHERE end_record.employee_id = ?
            AND end_record.type = 'overtime-end'
            AND end_record.timestamp > attendances.timestamp
            AND end_record.attendance_date = CURDATE()
      )
    LIMIT 1
");
$stmt->execute([$employee['id'], $employee['id']]);
if ($stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'يوجد دوام إضافي مفتوح بالفعل. أنهِه أولاً.'], 400);
}

// تسجيل بداية الدوام الإضافي
$result = recordAttendance($employee['id'], 'overtime-start', $lat, $lon, $accuracy);

if (!$result['success']) {
    jsonResponse($result, 400);
}

jsonResponse([
    'success'       => true,
    'message'       => 'تم تسجيل بدء الدوام الإضافي بنجاح',
    'employee_name' => $employee['name'],
    'timestamp'     => date('Y-m-d H:i:s')
]);

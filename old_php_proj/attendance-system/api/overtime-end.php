<?php
// =============================================================
// api/overtime-end.php - API إنهاء الدوام الإضافي
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');

// Rate Limiting
if (isRateLimited(20, 60, 'overtime-end')) { rateLimitResponse(); }
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

// التحقق من وجود session دوام إضافي مفتوحة
$stmt = db()->prepare("
    SELECT id, timestamp
    FROM attendances
    WHERE employee_id = ? 
      AND type = 'overtime-start' 
      AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
      AND NOT EXISTS (
          SELECT 1 FROM attendances end_record
          WHERE end_record.employee_id = ?
            AND end_record.type = 'overtime-end'
            AND end_record.timestamp > attendances.timestamp
      )
    ORDER BY timestamp DESC
    LIMIT 1
");
$stmt->execute([$employee['id'], $employee['id']]);
$overtimeSession = $stmt->fetch();

if (!$overtimeSession) {
    jsonResponse(['success' => false, 'message' => 'لا يوجد دوام إضافي مفتوح لإنهائه'], 400);
}

// حساب مدة الدوام الإضافي
$startTime = new DateTime($overtimeSession['timestamp']);
$endTime = new DateTime();
$duration = $endTime->diff($startTime);
$durationMinutes = ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i;

// التحقق من الحد الأدنى للمدة (30 دقيقة افتراضياً)
$schedule = getBranchSchedule($employee['branch_id'] ?? null);
$minDuration = $schedule['overtime_min_duration'] ?? 30;

if ($durationMinutes < $minDuration) {
    jsonResponse([
        'success' => false,
        'message' => "الدوام الإضافي يجب أن يكون {$minDuration} دقيقة على الأقل. المدة الحالية: {$durationMinutes} دقيقة"
    ], 400);
}

// تسجيل نهاية الدوام الإضافي
$result = recordAttendance($employee['id'], 'overtime-end', $lat, $lon, $accuracy);

if (!$result['success']) {
    jsonResponse($result, 400);
}

jsonResponse([
    'success'       => true,
    'message'       => 'تم إنهاء الدوام الإضافي بنجاح',
    'employee_name' => $employee['name'],
    'duration_minutes' => $durationMinutes,
    'duration_formatted' => sprintf('%dh %dm', floor($durationMinutes / 60), $durationMinutes % 60),
    'timestamp'     => date('Y-m-d H:i:s')
]);

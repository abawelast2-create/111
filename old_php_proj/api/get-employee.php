<?php
// =============================================================
// api/get-employee.php - API جلب بيانات موظف عبر token
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');

// Rate Limiting: 60 طلب/دقيقة لكل IP
if (isRateLimited(60, 60, 'get_employee')) { rateLimitResponse(); }

$token = (string)trim($_GET['token'] ?? '');
if (empty($token)) {
    jsonResponse(['success' => false, 'message' => 'token مطلوب'], 400);
}

$employee = getEmployeeByToken($token);
if (!$employee) {
    jsonResponse(['success' => false, 'message' => 'موظف غير موجود'], 404);
}

// آخر تسجيل اليوم
$stmt = db()->prepare("
    SELECT type, timestamp FROM attendances
    WHERE employee_id = ? AND attendance_date = CURDATE()
    ORDER BY timestamp DESC LIMIT 1
");
$stmt->execute([$employee['id']]);
$last = $stmt->fetch();

jsonResponse([
    'success'      => true,
    'employee'     => [
        'id'        => $employee['id'],
        'name'      => $employee['name'],
        'job_title' => $employee['job_title'],
    ],
    'last_record' => $last ?: null
]);

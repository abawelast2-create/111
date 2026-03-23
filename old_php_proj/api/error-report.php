<?php
// =============================================================
// api/error-report.php — تقارير الأخطاء السرية من أجهزة الموظفين
// يستقبل بلاغ خطأ بصمت ويحفظه كإشعار للمدير
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

// Rate Limiting: 20 تقرير/دقيقة لكل IP
if (isRateLimited(20, 60, 'error_report')) {
    jsonResponse(['ok' => true]); // لا تكشف عن rate limit للموظف
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['ok' => true]);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    jsonResponse(['ok' => true]);
}

$token     = trim($body['token'] ?? '');
$errorType = trim($body['error_type'] ?? 'unknown');
$errorMsg  = trim($body['error_message'] ?? '');
$page      = trim($body['page'] ?? '');
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip        = getClientIP();

// تحديد الموظف (اختياري — قد لا يكون التوكن متاحاً)
$employeeId = null;
$employeeName = 'غير محدد';
if (!empty($token)) {
    $emp = getEmployeeByToken($token);
    if ($emp) {
        $employeeId = (int)$emp['id'];
        $employeeName = $emp['name'];
    }
}

// تنقية النصوص
$errorType = mb_substr($errorType, 0, 50);
$errorMsg  = mb_substr($errorMsg, 0, 500);
$page      = mb_substr($page, 0, 200);

// حفظ في جدول notifications كإشعار للمدير
try {
    $data = json_encode([
        'error_type' => $errorType,
        'page'       => $page,
        'ip'         => $ip,
        'user_agent' => mb_substr($userAgent, 0, 300),
        'timestamp'  => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);

    $title = "⚠️ خطأ: {$employeeName}";
    $message = "[{$errorType}] {$errorMsg}";

    $stmt = db()->prepare("INSERT INTO notifications (employee_id, type, title, message, data_json) VALUES (?, 'error_report', ?, ?, ?)");
    $stmt->execute([$employeeId, $title, $message, $data]);
} catch (\Exception $e) {
    // صمت تام — لا نكشف أي خطأ
}

// دائماً أرجع OK — الموظف لا يعرف شيئاً
jsonResponse(['ok' => true]);

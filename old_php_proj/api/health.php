<?php
// =============================================================
// api/health.php - فحص صحة النظام
// =============================================================

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$checks = [
    'php'      => true,
    'database' => false,
    'disk'     => false,
];

$status = 'healthy';
$details = [];

// فحص PHP — لا نكشف إصدار PHP للحماية من الاستهداف
$details['php'] = 'ok';

// فحص قاعدة البيانات
try {
    require_once __DIR__ . '/../includes/db.php';
    $pdo = db();
    $pdo->query("SELECT 1");
    $checks['database'] = true;
    $details['database'] = 'connected';
} catch (Exception $e) {
    $checks['database'] = false;
    $details['database'] = 'connection failed';
    $status = 'unhealthy';
}

// فحص الكتابة على القرص
$tmpFile = sys_get_temp_dir() . '/attendance_health_' . time();
if (@file_put_contents($tmpFile, 'ok') !== false) {
    $checks['disk'] = true;
    $details['disk'] = 'writable';
    @unlink($tmpFile);
} else {
    $checks['disk'] = false;
    $details['disk'] = 'not writable';
    $status = 'degraded';
}

// وقت الخادم
$details['server_time'] = date('Y-m-d H:i:s');
$details['timezone']    = date_default_timezone_get();

$httpCode = $status === 'healthy' ? 200 : 503;

http_response_code($httpCode);
echo json_encode([
    'status'  => $status,
    'checks'  => $checks,
    'details' => $details,
    'uptime'  => time() - (int)(@filemtime(__DIR__ . '/../install.lock') ?: time()),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

<?php
// =============================================================
// api/verify-device.php — التحقق من بصمة الجهاز وتسجيلها
// وضع الربط:
//   0 = حر (بدون ربط)
//   1 = ربط صارم (يمنع الأجهزة المختلفة)
//   2 = ربط مراقبة (يسمح لكن يسجل تلاعب بصمت)
// =============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

header('Content-Type: application/json; charset=utf-8');

// Rate Limiting: 30 طلب/دقيقة لكل IP
if (isRateLimited(30, 60, 'verify')) {
    rateLimitResponse();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$token       = trim($data['token']       ?? '');
$fingerprint = trim($data['fingerprint'] ?? '');

if (!$token || !$fingerprint) {
    echo json_encode(['success' => false, 'message' => 'بيانات ناقصة']);
    exit;
}

// إنشاء جدول الأجهزة المعروفة إن لم يكن موجوداً
try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS known_devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fingerprint VARCHAR(64) NOT NULL,
            employee_id INT NOT NULL,
            usage_count INT NOT NULL DEFAULT 1,
            first_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_fp_emp (fingerprint, employee_id),
            KEY idx_fp (fingerprint),
            KEY idx_emp (employee_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) { /* تجاهل */
}

// جلب الموظف
$stmt = db()->prepare("SELECT id, name, device_fingerprint, device_bind_mode FROM employees WHERE unique_token = ? AND is_active = 1");
$stmt->execute([$token]);
$employee = $stmt->fetch();

if (!$employee) {
    echo json_encode(['success' => false, 'message' => 'رابط غير صالح']);
    exit;
}

// تسجيل استخدام الجهاز (تحديد ملكية الجهاز)
try {
    $upsert = db()->prepare("
        INSERT INTO known_devices (fingerprint, employee_id, usage_count, first_used_at, last_used_at)
        VALUES (?, ?, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE usage_count = usage_count + 1, last_used_at = NOW()
    ");
    $upsert->execute([$fingerprint, $employee['id']]);
} catch (Exception $e) { /* تجاهل */
}

$bindMode = (int)($employee['device_bind_mode'] ?? 0);

// ── لا توجد بصمة محفوظة بعد ──
if (empty($employee['device_fingerprint'])) {
    if ($bindMode === 1 || $bindMode === 2) {
        // وضع الربط مفعّل (صارم أو مراقبة) → ربط الجهاز
        $upd = db()->prepare("UPDATE employees SET device_fingerprint = ?, device_registered_at = NOW() WHERE id = ?");
        $upd->execute([$fingerprint, $employee['id']]);

        // التحقق: هل هذا الجهاز مربوط بموظف آخر أيضاً؟ (تسجيل بالنيابة محتمل)
        $otherStmt = db()->prepare("SELECT id, name FROM employees WHERE device_fingerprint = ? AND id != ? AND is_active = 1 AND deleted_at IS NULL");
        $otherStmt->execute([$fingerprint, $employee['id']]);
        $otherEmp = $otherStmt->fetch();
        if ($otherEmp) {
            logTampering($employee['id'], 'proxy_checkin', 'نفس الجهاز مربوط بموظف آخر: ' . $otherEmp['name'], 'medium', [
                'other_employee_id' => $otherEmp['id'],
                'other_employee_name' => $otherEmp['name'],
                'fingerprint' => substr($fingerprint, 0, 12) . '...',
            ]);
        }

        echo json_encode(['success' => true, 'first_time' => true, 'auto_bound' => true]);
        exit;
    }

    // وضع حر → اسمح بدون ربط
    echo json_encode(['success' => true, 'first_time' => true, 'auto_bound' => false]);
    exit;
}

// ── بصمة محفوظة — تحقق منها ──
if (hash_equals($employee['device_fingerprint'], $fingerprint)) {
    // نفس الجهاز — ممتاز
    echo json_encode(['success' => true, 'first_time' => false]);
    exit;
}

// ── بصمة مختلفة ──
$details = [
    'expected' => substr($employee['device_fingerprint'], 0, 12) . '...',
    'actual' => substr($fingerprint, 0, 12) . '...',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'time' => date('Y-m-d H:i:s'),
    'bind_mode' => $bindMode,
];

if ($bindMode === 1) {
    // ── وضع صارم: امنع الدخول ──
    logTampering($employee['id'], 'different_device', 'محاولة دخول من جهاز مختلف (تم المنع)', 'high', $details);

    echo json_encode([
        'success' => false,
        'locked' => true,
        'message' => 'هذا الرابط مربوط بجهاز آخر. لا يمكنك استخدامه من هذا الجهاز.',
    ]);
    exit;
}

if ($bindMode === 2) {
    // ── وضع مراقبة: اسمح بصمت + سجّل تلاعب ──
    logTampering($employee['id'], 'different_device', 'تسجيل من جهاز مختلف (وضع مراقبة — لم يُمنع)', 'medium', $details);

    // هل هذا الجهاز مربوط بموظف آخر؟
    $otherStmt = db()->prepare("SELECT id, name FROM employees WHERE device_fingerprint = ? AND id != ? AND is_active = 1 AND deleted_at IS NULL");
    $otherStmt->execute([$fingerprint, $employee['id']]);
    $otherEmp = $otherStmt->fetch();
    if ($otherEmp) {
        logTampering($employee['id'], 'proxy_checkin', 'يستخدم جهاز الموظف: ' . $otherEmp['name'] . ' — تسجيل بالنيابة محتمل', 'high', [
            'other_employee_id' => $otherEmp['id'],
            'other_employee_name' => $otherEmp['name'],
            'fingerprint' => substr($fingerprint, 0, 12) . '...',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }

    // اسمح بالدخول — الموظف لا يشعر بشيء
    echo json_encode(['success' => true, 'first_time' => false]);
    exit;
}

// ── وضع حر (device_bind_mode=0) مع وجود بصمة قديمة ──
logTampering($employee['id'], 'different_device', 'تسجيل من جهاز مختلف عن الجهاز المربوط', 'medium', $details);
echo json_encode(['success' => true, 'first_time' => false, 'device_mismatch' => true]);

// ================================================================
// Helper: تسجيل حالة تلاعب
// ================================================================
function logTampering($empId, $type, $desc, $severity, $details)
{
    try {
        $stmt = db()->prepare("INSERT INTO tampering_cases (employee_id, case_type, description, attendance_date, severity, details_json) VALUES (?, ?, ?, CURDATE(), ?, ?)");
        $stmt->execute([$empId, $type, $desc, $severity, json_encode($details, JSON_UNESCAPED_UNICODE)]);
    } catch (Exception $e) { /* الجدول قد لا يكون موجوداً */
    }
}

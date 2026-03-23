<?php
// =============================================================
// api/leave-add.php - إضافة إجازة جديدة
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/admin/leaves.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'طلب غير صالح، يرجى المحاولة مجدداً';
    header('Location: ' . SITE_URL . '/admin/leaves.php');
    exit;
}

$employeeId = (int)($_POST['employee_id'] ?? 0);
$leaveType  = $_POST['leave_type'] ?? '';
$startDate  = $_POST['start_date'] ?? '';
$endDate    = $_POST['end_date'] ?? '';
$reason     = trim($_POST['reason'] ?? '');

// Validate
$errors = [];
if ($employeeId <= 0) $errors[] = 'يجب اختيار موظف';
if (!in_array($leaveType, ['annual', 'sick', 'unpaid', 'other'])) $errors[] = 'نوع الإجازة غير صالح';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $errors[] = 'تاريخ البداية غير صالح';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $errors[] = 'تاريخ النهاية غير صالح';
if ($startDate > $endDate) $errors[] = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';

// Check employee exists
if (empty($errors)) {
    $emp = db()->prepare("SELECT id FROM employees WHERE id = ? AND is_active = 1 AND deleted_at IS NULL");
    $emp->execute([$employeeId]);
    if (!$emp->fetch()) $errors[] = 'الموظف غير موجود';
}

// Check overlapping leaves
if (empty($errors)) {
    $overlap = db()->prepare("
        SELECT id FROM leaves
        WHERE employee_id = ? AND status != 'rejected'
          AND start_date <= ? AND end_date >= ?
    ");
    $overlap->execute([$employeeId, $endDate, $startDate]);
    if ($overlap->fetch()) $errors[] = 'يوجد تداخل مع إجازة سابقة لهذا الموظف';
}

if (!empty($errors)) {
    $_SESSION['flash_error'] = implode('، ', $errors);
    header('Location: ' . SITE_URL . '/admin/leaves.php');
    exit;
}

// Insert
$stmt = db()->prepare("
    INSERT INTO leaves (employee_id, leave_type, start_date, end_date, reason, status, approved_by)
    VALUES (?, ?, ?, ?, ?, 'approved', ?)
");
$stmt->execute([
    $employeeId,
    $leaveType,
    $startDate,
    $endDate,
    $reason ?: null,
    $_SESSION['admin_id']
]);

$_SESSION['flash_success'] = 'تمت إضافة الإجازة بنجاح';
header('Location: ' . SITE_URL . '/admin/leaves.php');
exit;

<?php
// =============================================================
// api/whatsapp.php - توليد روابط واتساب لجميع الموظفين أو موظف محدد
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();

$empId = (int)($_GET['emp_id'] ?? 0);

if ($empId > 0) {
    // موظف واحد
    $emp = db()->prepare("SELECT * FROM employees WHERE id=? AND is_active=1 AND deleted_at IS NULL");
    $emp->execute([$empId]);
    $employee = $emp->fetch();
    if (!$employee) {
        jsonResponse(['success' => false, 'message' => 'موظف غير موجود'], 404);
    }
    $link = SITE_URL . '/employee/attendance.php?token=' . $employee['unique_token'];
    $wa   = $employee['phone'] ? generateWhatsAppLink($employee['phone'], $employee['unique_token']) : null;
    jsonResponse(['success' => true, 'link' => $link, 'whatsapp' => $wa, 'employee' => $employee['name']]);
} else {
    // جميع الموظفين
    $all = db()->query("SELECT id, name, phone, unique_token FROM employees WHERE is_active=1 AND deleted_at IS NULL ORDER BY name")->fetchAll();
    $result = [];
    foreach ($all as $e) {
        $link = SITE_URL . '/employee/attendance.php?token=' . $e['unique_token'];
        $result[] = [
            'id'       => $e['id'],
            'name'     => $e['name'],
            'phone'    => $e['phone'],
            'link'     => $link,
            'whatsapp' => $e['phone'] ? generateWhatsAppLink($e['phone'], $e['unique_token']) : null,
        ];
    }
    jsonResponse(['success' => true, 'employees' => $result, 'count' => count($result)]);
}

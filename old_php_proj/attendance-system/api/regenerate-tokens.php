<?php
// =============================================================
// api/regenerate-tokens.php - إعادة توليد روابط الموظفين
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل دخول المدير
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'طريقة طلب غير مسموحة'], 405);
}

// التحقق من CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'رمز الأمان غير صحيح'], 403);
}

// نوع العملية: all (الكل) أو employee_id (موظف واحد)
$action = $_POST['action'] ?? 'all';
$employeeId = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;

try {
    $updated = 0;
    
    if ($action === 'all') {
        // تحديث جميع الموظفين النشطين
        $stmt = db()->query("SELECT id, name FROM employees WHERE is_active = 1 AND deleted_at IS NULL");
        $employees = $stmt->fetchAll();
        
        $updateStmt = db()->prepare("UPDATE employees SET unique_token = ? WHERE id = ?");
        
        foreach ($employees as $emp) {
            $newToken = generateUniqueToken();
            $updateStmt->execute([$newToken, $emp['id']]);
            $updated++;
            
            // تسجيل في audit log
            auditLog('regenerate_token', "إعادة توليد رابط للموظف: {$emp['name']}", $emp['id']);
        }
        
        $message = "تم تحديث {$updated} رابط بنجاح";
        
    } elseif ($action === 'single' && $employeeId) {
        // تحديث موظف واحد
        $stmt = db()->prepare("SELECT id, name FROM employees WHERE id = ? AND is_active = 1 AND deleted_at IS NULL");
        $stmt->execute([$employeeId]);
        $employee = $stmt->fetch();
        
        if (!$employee) {
            jsonResponse(['success' => false, 'message' => 'الموظف غير موجود أو غير نشط'], 404);
        }
        
        $newToken = generateUniqueToken();
        $updateStmt = db()->prepare("UPDATE employees SET unique_token = ? WHERE id = ?");
        $updateStmt->execute([$newToken, $employeeId]);
        $updated = 1;
        
        // تسجيل في audit log
        auditLog('regenerate_token', "إعادة توليد رابط للموظف: {$employee['name']}", $employeeId);
        
        $message = "تم تحديث الرابط للموظف: {$employee['name']}";
        
        // إرجاع الرابط الجديد
        $newLink = SITE_URL . '/employee/attendance.php?token=' . $newToken;
        jsonResponse([
            'success' => true,
            'message' => $message,
            'updated' => $updated,
            'new_token' => $newToken,
            'new_link' => $newLink
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'عملية غير صالحة'], 400);
    }
    
    jsonResponse([
        'success' => true,
        'message' => $message,
        'updated' => $updated
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], 500);
}

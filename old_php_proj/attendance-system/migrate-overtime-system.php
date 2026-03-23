<?php
/**
 * ================================================================
 * migrate-overtimesystem.php - Migration: Update overtime system
 * ================================================================
 * يحدّث جدول attendances ليدعم:
 * - overtime-start (بداية الدوام الإضافي)
 * - overtime-end (انتهاء الدوام الإضافي)
 * ================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$log = [];

try {
    $pdo = db();

    // التحقق من نوع جدول attendances الحالي
    $stmt = $pdo->query("SHOW COLUMNS FROM attendances LIKE 'type'");
    $column = $stmt->fetch();
    
    if ($column && strpos($column['Type'], 'overtime-start') === false) {
        // تحديث ENUM ليشمل overtime-start و overtime-end
        $pdo->exec("
            ALTER TABLE attendances
            MODIFY COLUMN type ENUM('in','out','overtime','overtime-start','overtime-end') NOT NULL
        ");
        $log[] = '✅ تم تحديث جدول attendances: أضيف overtime-start و overtime-end';
    } else {
        $log[] = '⏭️ ENUM يحتوي بالفعل على overtime-start';
    }

    // تحويل البيانات القديمة: overtime → overtime-start
    $pdo->exec("
        UPDATE attendances
        SET type = 'overtime-start'
        WHERE type = 'overtime'
    ");
    $affected = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
    $log[] = "✅ تم تحويل {$affected} سجل من overtime إلى overtime-start";

    $log[] = '✅ Migration completed successfully';

} catch (Exception $e) {
    $log[] = '❌ خطأ: ' . $e->getMessage();
}

foreach ($log as $line) {
    echo $line . PHP_EOL;
}

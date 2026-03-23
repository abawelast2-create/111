<?php
// =============================================================
// migrate-shifts-v2.php — تحويل النظام إلى نظام الورديات
// يُنفَّذ مرة واحدة ثم يحذف نفسه
// =============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$log = [];

try {
    // 1. إنشاء جدول الورديات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS branch_shifts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            branch_id INT NOT NULL,
            shift_number TINYINT NOT NULL DEFAULT 1,
            shift_start TIME NOT NULL,
            shift_end TIME NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
            UNIQUE KEY uq_branch_shift (branch_id, shift_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $log[] = '✅ جدول branch_shifts تم إنشاؤه';

    // 2. إدراج وردية افتراضية (12:00 - 16:00) لكل فرع
    $branches = $pdo->query("SELECT id FROM branches")->fetchAll();
    foreach ($branches as $b) {
        $chk = $pdo->prepare("SELECT id FROM branch_shifts WHERE branch_id = ? AND shift_number = 1");
        $chk->execute([$b['id']]);
        if (!$chk->fetch()) {
            $ins = $pdo->prepare("INSERT INTO branch_shifts (branch_id, shift_number, shift_start, shift_end) VALUES (?, 1, '12:00', '16:00')");
            $ins->execute([$b['id']]);
            $log[] = "✅ وردية 1 (12:00-16:00) للفرع ID:{$b['id']}";
        } else {
            $log[] = "⚠️ وردية 1 موجودة مسبقاً للفرع ID:{$b['id']}";
        }
    }

    // 3. حذف الأعمدة القديمة من branches
    $dropCols = [
        'check_in_start_time', 'check_in_end_time',
        'check_out_start_time', 'check_out_end_time',
        'checkout_show_before',
        'break_start', 'break_end',
        'work_start_time', 'work_end_time'
    ];
    foreach ($dropCols as $col) {
        try {
            $pdo->exec("ALTER TABLE branches DROP COLUMN `{$col}`");
            $log[] = "✅ حُذف عمود {$col}";
        } catch (Exception $e) {
            $log[] = "⚠️ عمود {$col} غير موجود أو حُذف مسبقاً";
        }
    }

    $log[] = '';
    $log[] = '🎉 اكتمل التحويل بنجاح!';

    // 4. حذف الملف تلقائياً
    @unlink(__FILE__);
    $log[] = '🗑️ تم حذف ملف الهجرة تلقائياً';

} catch (Exception $e) {
    $log[] = '❌ خطأ: ' . $e->getMessage();
}

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $log);

<?php
// =============================================================
// migrate-v3-columns.php — إضافة أعمدة v3.0 المفقودة
// يُشغَّل مرة واحدة من المتصفح لإصلاح خطأ 500 في late-report
// =============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo = db();
$log = [];

// جلب أعمدة جدول attendances الحالية
$existingCols = $pdo->query("DESCRIBE attendances")->fetchAll(PDO::FETCH_COLUMN);

// =================== 1. attendance_date ===================
try {
    if (!in_array('attendance_date', $existingCols)) {
        $pdo->exec("ALTER TABLE attendances ADD COLUMN attendance_date DATE NULL AFTER employee_id");
        // تعبئة القيم الموجودة من timestamp
        $pdo->exec("UPDATE attendances SET attendance_date = DATE(timestamp) WHERE attendance_date IS NULL");
        $log[] = '✅ تم إضافة عمود attendance_date';
    } else {
        $log[] = '⏭️ attendance_date موجود مسبقاً';
    }
} catch (PDOException $e) {
    $log[] = '❌ attendance_date: ' . $e->getMessage();
}

// =================== 2. late_minutes ===================
try {
    if (!in_array('late_minutes', $existingCols)) {
        $pdo->exec("ALTER TABLE attendances ADD COLUMN late_minutes INT NOT NULL DEFAULT 0 AFTER attendance_date");
        $log[] = '✅ تم إضافة عمود late_minutes';
    } else {
        $log[] = '⏭️ late_minutes موجود مسبقاً';
    }
} catch (PDOException $e) {
    $log[] = '❌ late_minutes: ' . $e->getMessage();
}

// =================== 3. type ENUM (إذا كان VARCHAR قديمًا) ===================
try {
    $col = $pdo->query("SHOW COLUMNS FROM attendances LIKE 'type'")->fetch(PDO::FETCH_ASSOC);
    if ($col && stripos($col['Type'], 'enum') === false) {
        $pdo->exec("ALTER TABLE attendances MODIFY COLUMN type ENUM('in','out','overtime_start','overtime_end') NOT NULL DEFAULT 'in'");
        $log[] = '✅ تم تحديث نوع عمود type إلى ENUM';
    } else {
        $log[] = '⏭️ عمود type بالصيغة الصحيحة';
    }
} catch (PDOException $e) {
    $log[] = '❌ type ENUM: ' . $e->getMessage();
}

// =================== 4. الفهرس على late_minutes ===================
try {
    $indexes = $pdo->query("SHOW INDEX FROM attendances WHERE Key_name = 'idx_late'")->fetchAll();
    if (empty($indexes)) {
        $pdo->exec("ALTER TABLE attendances ADD INDEX idx_late (type, late_minutes, attendance_date)");
        $log[] = '✅ تم إضافة فهرس idx_late';
    } else {
        $log[] = '⏭️ فهرس idx_late موجود مسبقاً';
    }
} catch (PDOException $e) {
    $log[] = '❌ idx_late: ' . $e->getMessage();
}

// =================== 5. التحقق النهائي ===================
$finalCols = $pdo->query("DESCRIBE attendances")->fetchAll(PDO::FETCH_ASSOC);
$colNames  = array_column($finalCols, 'Field');
$required  = ['attendance_date', 'late_minutes', 'type'];
$missing   = array_diff($required, $colNames);

$allOk = empty($missing);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Migration v3 Columns</title>
<style>
  body { font-family: Arial, sans-serif; background: #0F172A; color: #E2E8F0; padding: 30px; direction: rtl; }
  h2   { color: #D4A841; }
  .ok  { color: #4ADE80; }
  .err { color: #F87171; }
  .skip{ color: #94A3B8; }
  pre  { background: #1E293B; padding: 16px; border-radius: 8px; }
  .btn { display:inline-block; padding:10px 22px; background:#059669; color:#fff;
         border-radius:8px; text-decoration:none; font-weight:bold; margin-top:16px; }
  .warn{ background:#7C2D12; border:1px solid #F87171; border-radius:8px; padding:12px; margin-top:16px; }
</style>
</head>
<body>
<h2>🔧 Migration v3.0 — إضافة الأعمدة المفقودة</h2>

<pre><?php foreach ($log as $line) {
    $class = str_starts_with($line, '✅') ? 'ok' : (str_starts_with($line, '❌') ? 'err' : 'skip');
    echo "<span class=\"{$class}\">" . htmlspecialchars($line) . "</span>\n";
} ?></pre>

<?php if ($allOk): ?>
  <p class="ok">✅ <strong>اكتملت العملية بنجاح — جدول attendances جاهز</strong></p>
  <a href="admin/late-report.php" class="btn">📊 افتح تقرير التأخير</a>
<?php else: ?>
  <p class="err">❌ <strong>الأعمدة التالية لا تزال مفقودة: <?= implode(', ', $missing) ?></strong></p>
<?php endif; ?>

<div class="warn">
  <strong>⚠️ تنبيه أمني:</strong> احذف هذا الملف من السيرفر بعد الانتهاء.<br>
  <code>delete: migrate-v3-columns.php</code>
</div>

<h3 style="color:#94A3B8;margin-top:24px">أعمدة attendance الحالية:</h3>
<pre><?php foreach ($finalCols as $c): ?>
  <span class="<?= in_array($c['Field'], $required) ? 'ok' : '' ?>"><?= htmlspecialchars("{$c['Field']} — {$c['Type']} — {$c['Null']} — Default:{$c['Default']}") ?></span>
<?php endforeach; ?></pre>
</body>
</html>

<?php
// archive_reset.php — أرشفة جميع السجلات وتصفير الإحصائيات
// يُستخدم مرة واحدة ثم يُحذف
// =============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAdminLogin();

$results = [];
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'YES') {

    $pdo = db();
    $pdo->beginTransaction();

    try {
        // ═══════════════════════════════════════════
        // 1) أرشفة سجلات الحضور
        // ═══════════════════════════════════════════
        $pdo->exec("CREATE TABLE IF NOT EXISTS attendances_archive LIKE attendances");
        $count = $pdo->exec("INSERT INTO attendances_archive SELECT * FROM attendances");
        $results[] = "✅ تم أرشفة {$count} سجل حضور → attendances_archive";

        // 2) أرشفة حالات التلاعب
        $pdo->exec("CREATE TABLE IF NOT EXISTS tampering_cases_archive LIKE tampering_cases");
        $count2 = $pdo->exec("INSERT INTO tampering_cases_archive SELECT * FROM tampering_cases");
        $results[] = "✅ تم أرشفة {$count2} حالة تلاعب → tampering_cases_archive";

        // 3) أرشفة البلاغات السرية
        $pdo->exec("CREATE TABLE IF NOT EXISTS secret_reports_archive LIKE secret_reports");
        $count3 = $pdo->exec("INSERT INTO secret_reports_archive SELECT * FROM secret_reports");
        $results[] = "✅ تم أرشفة {$count3} بلاغ سري → secret_reports_archive";

        // 4) أرشفة سجل التدقيق
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log_archive LIKE audit_log");
        $count4 = $pdo->exec("INSERT INTO audit_log_archive SELECT * FROM audit_log");
        $results[] = "✅ تم أرشفة {$count4} سجل تدقيق → audit_log_archive";

        // 5) أرشفة الأجهزة المعروفة
        $pdo->exec("CREATE TABLE IF NOT EXISTS known_devices_archive LIKE known_devices");
        $count5 = $pdo->exec("INSERT INTO known_devices_archive SELECT * FROM known_devices");
        $results[] = "✅ تم أرشفة {$count5} جهاز → known_devices_archive";

        // ═══════════════════════════════════════════
        // تصفير جميع الجداول
        // ═══════════════════════════════════════════
        $pdo->exec("DELETE FROM attendances");
        $results[] = "🗑️ تم تصفير جدول الحضور (attendances)";

        $pdo->exec("DELETE FROM tampering_cases");
        $results[] = "🗑️ تم تصفير جدول التلاعب (tampering_cases)";

        $pdo->exec("DELETE FROM secret_reports");
        $results[] = "🗑️ تم تصفير جدول البلاغات (secret_reports)";

        $pdo->exec("DELETE FROM audit_log");
        $results[] = "🗑️ تم تصفير سجل التدقيق (audit_log)";

        $pdo->exec("DELETE FROM known_devices");
        $results[] = "🗑️ تم تصفير جدول الأجهزة (known_devices)";

        $pdo->exec("DELETE FROM login_attempts");
        $results[] = "🗑️ تم تصفير محاولات الدخول (login_attempts)";

        // تصفير بصمات الأجهزة من الموظفين
        $pdo->exec("UPDATE employees SET device_fingerprint=NULL, device_registered_at=NULL WHERE deleted_at IS NULL");
        $results[] = "🔓 تم إزالة بصمات الأجهزة من جميع الموظفين";

        $pdo->commit();

        // تسجيل العملية في سجل التدقيق الجديد
        auditLog('archive_reset', 'أرشفة وتصفير جميع السجلات والإحصائيات');

        $results[] = "";
        $results[] = "═══════════════════════════════════════";
        $results[] = "✅ تمت العملية بنجاح — النظام جاهز من الصفر";
        $results[] = "═══════════════════════════════════════";

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "❌ فشلت العملية: " . $e->getMessage();
    }
}

// إحصائيات حالية
$stats = [];
try {
    $pdo = db();
    $stats['attendances']      = (int)$pdo->query("SELECT COUNT(*) FROM attendances")->fetchColumn();
    $stats['tampering_cases']  = (int)$pdo->query("SELECT COUNT(*) FROM tampering_cases")->fetchColumn();
    $stats['secret_reports']   = (int)$pdo->query("SELECT COUNT(*) FROM secret_reports")->fetchColumn();
    $stats['audit_log']        = (int)$pdo->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
    $stats['known_devices']    = (int)$pdo->query("SELECT COUNT(*) FROM known_devices")->fetchColumn();
    $stats['login_attempts']   = (int)$pdo->query("SELECT COUNT(*) FROM login_attempts")->fetchColumn();
    $stats['devices_bound']    = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE device_fingerprint IS NOT NULL AND deleted_at IS NULL")->fetchColumn();
} catch (Exception $e) {
    $errors[] = "خطأ في قراءة الإحصائيات: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>أرشفة وتصفير النظام</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/fonts/tajawal.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Tajawal',sans-serif; background:#0F172A; color:#E2E8F0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .container { max-width:600px; width:100%; }
        .card { background:#1E293B; border-radius:16px; padding:30px; margin-bottom:20px; border:1px solid #334155; }
        h1 { font-size:1.5rem; margin-bottom:20px; color:#F97316; text-align:center; }
        .stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin:20px 0; }
        .stat-item { background:#0F172A; border-radius:10px; padding:12px; text-align:center; }
        .stat-value { font-size:1.8rem; font-weight:800; color:#F97316; }
        .stat-label { font-size:.8rem; color:#94A3B8; margin-top:4px; }
        .btn { display:inline-block; padding:12px 28px; border-radius:10px; font-family:inherit; font-size:1rem; font-weight:700; cursor:pointer; border:none; transition:.2s; }
        .btn-danger { background:#EF4444; color:#fff; width:100%; }
        .btn-danger:hover { background:#DC2626; }
        .btn-back { background:#334155; color:#E2E8F0; text-decoration:none; text-align:center; width:100%; margin-top:10px; }
        .result { margin-top:10px; padding:8px 14px; background:#0F172A; border-radius:8px; font-size:.85rem; line-height:1.8; }
        .result.success { border-right:3px solid #22C55E; }
        .result.error { border-right:3px solid #EF4444; }
        .warn { background:#7C2D12; border:1px solid #F97316; border-radius:10px; padding:14px; margin:16px 0; font-size:.85rem; color:#FDBA74; line-height:1.7; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>🗄️ أرشفة وتصفير النظام</h1>

        <?php if (!empty($results)): ?>
            <div class="result success">
                <?php foreach ($results as $r): ?>
                    <div><?= htmlspecialchars($r) ?></div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:20px;text-align:center">
                <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-back" style="display:inline-block">← الرجوع للوحة التحكم</a>
            </div>
        <?php elseif (!empty($errors)): ?>
            <div class="result error">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center;color:#94A3B8;margin-bottom:16px">الإحصائيات الحالية قبل التصفير:</p>

            <div class="stat-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['attendances'] ?? 0) ?></div>
                    <div class="stat-label">سجل حضور</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['tampering_cases'] ?? 0) ?></div>
                    <div class="stat-label">حالة تلاعب</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['secret_reports'] ?? 0) ?></div>
                    <div class="stat-label">بلاغ سري</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['audit_log'] ?? 0) ?></div>
                    <div class="stat-label">سجل تدقيق</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['known_devices'] ?? 0) ?></div>
                    <div class="stat-label">جهاز معروف</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['devices_bound'] ?? 0) ?></div>
                    <div class="stat-label">جهاز مربوط</div>
                </div>
            </div>

            <div class="warn">
                ⚠️ <strong>تحذير:</strong> هذه العملية ستقوم بـ:
                <br>1. نسخ جميع السجلات إلى جداول أرشيف (_archive)
                <br>2. حذف جميع سجلات الحضور
                <br>3. حذف جميع حالات التلاعب
                <br>4. حذف جميع البلاغات السرية
                <br>5. حذف سجل التدقيق
                <br>6. حذف الأجهزة المعروفة
                <br>7. إزالة بصمات الأجهزة من الموظفين
                <br><br>⚡ <strong>البيانات ستبقى محفوظة في جداول الأرشيف</strong>
            </div>

            <form method="POST">
                <input type="hidden" name="confirm" value="YES">
                <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ هل أنت متأكد تماماً؟\n\nسيتم أرشفة جميع السجلات وتصفير النظام.')">
                    🗄️ أرشفة وتصفير الآن
                </button>
            </form>
            <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-back" style="display:block;margin-top:12px;text-align:center">← إلغاء والرجوع</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
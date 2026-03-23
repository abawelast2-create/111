<?php
// =============================================================
// migrate-v4.php - ترقية قاعدة البيانات للإصدار 4.0
// =============================================================
// يُشغَّل مرة واحدة لإضافة الأعمدة والفهارس الجديدة
// =============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$log = [];
$pdo = db();

try {
    // =================== 1. عمود 2FA للمديرين ===================
    try {
        $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_secret VARCHAR(64) NULL AFTER password_hash");
        $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER two_factor_secret");
        $log[] = '✅ تم إضافة أعمدة المصادقة الثنائية (2FA) للمديرين';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            $log[] = '⏭️ أعمدة 2FA موجودة مسبقاً';
        } else {
            $log[] = '❌ خطأ 2FA: ' . $e->getMessage();
        }
    }

    // =================== 2. فهارس مركبة محسنة ===================
    $indexes = [
        // فهرس مركب لاستعلامات التقارير الشائعة
        ["attendances", "idx_emp_date_type", "(employee_id, attendance_date, type)"],
        // فهرس لحساب الإحصائيات
        ["attendances", "idx_date_type", "(attendance_date, type)"],
        // فهرس لاستعلامات التأخير
        ["attendances", "idx_late", "(type, late_minutes, attendance_date)"],
        // فهرس للموظفين النشطين
        ["employees", "idx_active_branch", "(is_active, deleted_at, branch_id)"],
        // فهرس للتوكن
        ["employees", "idx_token_active", "(unique_token, is_active, deleted_at)"],
        // فهرس للـ PIN
        ["employees", "idx_pin_active", "(pin, is_active, deleted_at)"],
        // فهرس للإجازات
        ["leaves", "idx_dates_status", "(start_date, end_date, status)"],
    ];

    foreach ($indexes as [$table, $name, $columns]) {
        try {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX {$name} {$columns}");
            $log[] = "✅ تم إضافة فهرس {$name} على {$table}";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                $log[] = "⏭️ فهرس {$name} موجود مسبقاً";
            } else {
                $log[] = "❌ خطأ فهرس {$name}: " . $e->getMessage();
            }
        }
    }

    // =================== 3. جدول الإشعارات ===================
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                employee_id     INT             NULL,
                admin_id        INT             NULL,
                type            VARCHAR(50)     NOT NULL,
                title           VARCHAR(255)    NOT NULL,
                message         TEXT            NULL,
                is_read         TINYINT(1)      DEFAULT 0,
                data_json       JSON            NULL,
                created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                read_at         TIMESTAMP       NULL,
                INDEX idx_employee (employee_id),
                INDEX idx_admin (admin_id),
                INDEX idx_type_read (type, is_read),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $log[] = '✅ تم إنشاء جدول notifications';
    } catch (PDOException $e) {
        $log[] = '⏭️ جدول notifications: ' . $e->getMessage();
    }

    // =================== 4. إعدادات جديدة ===================
    $newSettings = [
        ['dark_mode_default', '0', 'الوضع الداكن الافتراضي (0=فاتح، 1=داكن)'],
        ['session_timeout', '30', 'مهلة انتهاء الجلسة بالدقائق'],
        ['language', 'ar', 'اللغة الافتراضية'],
        ['enable_2fa', '0', 'تفعيل المصادقة الثنائية'],
        ['enable_notifications', '1', 'تفعيل الإشعارات'],
        ['enable_leave_management', '1', 'تفعيل إدارة الإجازات'],
        ['max_login_attempts', '5', 'الحد الأقصى لمحاولات الدخول'],
        ['login_lockout_minutes', '10', 'مدة الحظر بعد تجاوز المحاولات (دقائق)'],
        ['app_version', '4.0.0', 'إصدار التطبيق'],
    ];

    $stmtSet = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?,?,?)");
    foreach ($newSettings as $s) {
        $stmtSet->execute($s);
    }
    $log[] = '✅ تم إضافة الإعدادات الجديدة';

    // =================== 5. جدول التفضيلات (الوضع الداكن واللغة) ===================
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_preferences (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                admin_id    INT NOT NULL,
                pref_key    VARCHAR(50) NOT NULL,
                pref_value  VARCHAR(255) NULL,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_admin_pref (admin_id, pref_key),
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $log[] = '✅ تم إنشاء جدول user_preferences';
    } catch (PDOException $e) {
        $log[] = '⏭️ جدول user_preferences: ' . $e->getMessage();
    }

    $log[] = '';
    $log[] = '🎉 اكتملت ترقية قاعدة البيانات للإصدار 4.0';

} catch (Exception $e) {
    $log[] = '❌ خطأ عام: ' . $e->getMessage();
}

// =================== عرض النتائج ===================
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ترقية v4.0</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,sans-serif; background:#0F172A; color:#E2E8F0; padding:40px 20px; }
        .container { max-width:700px; margin:auto; background:#1E293B; border-radius:16px; padding:36px; box-shadow:0 20px 60px rgba(0,0,0,.5); }
        h1 { color:#D4A841; font-size:1.8rem; margin-bottom:24px; text-align:center; }
        .log-item { padding:10px 14px; margin:6px 0; border-radius:8px; font-size:.93rem; background:#0F172A; border-right:4px solid #D4A841; }
        .btn { display:inline-block; margin-top:24px; padding:12px 28px; background:#D4A841; color:#0F172A; border-radius:8px; text-decoration:none; font-weight:bold; }
        .version { text-align:center; color:#64748B; margin-top:20px; font-size:.85rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>⬆️ ترقية النظام إلى v4.0</h1>
    <?php foreach ($log as $item): ?>
        <div class="log-item"><?= htmlspecialchars($item) ?></div>
    <?php endforeach; ?>
    <div style="text-align:center">
        <a href="admin/dashboard.php" class="btn">📊 لوحة التحكم</a>
    </div>
    <div class="version">Attendance System v4.0.0</div>
</div>
</body>
</html>

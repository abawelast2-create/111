<?php
// =============================================================
// migrate-reports.php - إضافة جداول البلاغات السرية وحالات التلاعب
// =============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$log = [];

try {
  $pdo = db();

  // =================== جدول البلاغات السرية ===================
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS secret_reports (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            employee_id     INT             NOT NULL,
            report_text     TEXT            NULL,
            report_type     VARCHAR(50)     NOT NULL DEFAULT 'violation',
            has_image       TINYINT(1)      DEFAULT 0,
            image_path      VARCHAR(500)    NULL,
            has_voice       TINYINT(1)      DEFAULT 0,
            voice_path      VARCHAR(500)    NULL,
            voice_effect    VARCHAR(20)     NULL,
            status          ENUM('new','reviewed','archived') DEFAULT 'new',
            admin_notes     TEXT            NULL,
            created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
            reviewed_at     TIMESTAMP       NULL,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
  $log[] = '✅ تم إنشاء جدول secret_reports';

  // =================== جدول حالات التلاعب ===================
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS tampering_cases (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            employee_id     INT             NOT NULL,
            case_type       VARCHAR(50)     NOT NULL,
            description     TEXT            NULL,
            attendance_date DATE            NULL,
            severity        ENUM('low','medium','high') DEFAULT 'medium',
            details_json    JSON            NULL,
            created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
            INDEX idx_employee (employee_id),
            INDEX idx_type (case_type),
            INDEX idx_date (attendance_date),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
  $log[] = '✅ تم إنشاء جدول tampering_cases';

  echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><title>ترقية قاعدة البيانات</title>
    <style>body{font-family:Arial;background:#0F172A;color:#E2E8F0;padding:40px}
    .log{background:#1E293B;border-radius:12px;padding:20px;max-width:600px;margin:0 auto}
    .item{padding:8px 0;border-bottom:1px solid rgba(255,255,255,.1);font-size:.9rem}
    h2{color:#4ADE80;text-align:center;margin-bottom:20px}
    a{color:#60A5FA;text-decoration:none;display:block;text-align:center;margin-top:20px}
    </style></head><body><div class='log'><h2>✅ تم ترقية قاعدة البيانات</h2>";
  foreach ($log as $item) echo "<div class='item'>$item</div>";
  echo "<a href='admin/dashboard.php'>← العودة للوحة التحكم</a></div></body></html>";
} catch (PDOException $e) {
  echo "❌ خطأ: " . $e->getMessage();
}

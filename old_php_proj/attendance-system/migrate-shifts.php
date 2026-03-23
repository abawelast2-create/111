<?php
/**
 * migrate-shifts.php — تحديث جداول الفروع والإعدادات لنظام الورديتين
 * الوردية 1: 12:00 ظهرًا - 3:30 عصرًا
 * الوردية 2: 8:00 مساءً - 12:00 صباحًا
 *
 * القواعد الثابتة:
 * - الحضور يبدأ قبل ساعة ويتوقف بعد ساعتين
 * - الانصراف يبدأ قبل نصف ساعة
 * - انصراف تلقائي عند وقت النهاية
 * - العمل الإضافي متاح
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "=== Migration: Dual Shift System ===\n\n";

try {
    $pdo = db();

    // ── 1. Update ALL branches with Shift 1 schedule ──
    echo "1. Updating branches with Shift 1 schedule...\n";
    $pdo->exec("
        UPDATE branches SET
            work_start_time      = '12:00:00',
            work_end_time        = '15:30:00',
            check_in_start_time  = '11:00:00',
            check_in_end_time    = '14:00:00',
            check_out_start_time = '15:00:00',
            check_out_end_time   = '15:30:00',
            checkout_show_before = 0,
            allow_overtime       = 1
        WHERE is_active = 1
    ");
    $count = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
    echo "   ✅ Updated {$count} branches\n";

    // ── 2. Update system_settings with Shift 1 defaults ──
    echo "2. Updating system settings (Shift 1 defaults)...\n";
    $shift1Settings = [
        'work_start_time'      => '12:00',
        'work_end_time'        => '15:30',
        'check_in_start_time'  => '11:00',
        'check_in_end_time'    => '14:00',
        'check_out_start_time' => '15:00',
        'check_out_end_time'   => '15:30',
        'checkout_show_before' => '0',
        'allow_overtime'       => '1',
    ];

    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    foreach ($shift1Settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }
    echo "   ✅ Shift 1 settings updated\n";

    // ── 3. Add Shift 2 settings ──
    echo "3. Adding Shift 2 settings...\n";
    $shift2Settings = [
        'work_start_time_2'      => '20:00',
        'work_end_time_2'        => '00:00',
        'check_in_start_time_2'  => '19:00',
        'check_in_end_time_2'    => '22:00',
        'check_out_start_time_2' => '23:30',
        'check_out_end_time_2'   => '00:00',
    ];
    foreach ($shift2Settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }
    echo "   ✅ Shift 2 settings added\n";

    // ── 4. Verify ──
    echo "\n=== Verification ===\n";
    $branches = $pdo->query("SELECT id, name, work_start_time, work_end_time, check_in_start_time, check_in_end_time, check_out_start_time, check_out_end_time, allow_overtime FROM branches WHERE is_active = 1")->fetchAll();
    foreach ($branches as $b) {
        echo "   Branch #{$b['id']}: {$b['work_start_time']}-{$b['work_end_time']} | CI: {$b['check_in_start_time']}-{$b['check_in_end_time']} | CO: {$b['check_out_start_time']}-{$b['check_out_end_time']} | OT: {$b['allow_overtime']}\n";
    }

    $s2 = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%_2'")->fetchAll();
    echo "\n   Shift 2 settings:\n";
    foreach ($s2 as $s) {
        echo "   {$s['setting_key']} = {$s['setting_value']}\n";
    }

    echo "\n✅ Migration completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

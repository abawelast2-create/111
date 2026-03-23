<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// تحديث جميع الفروع: دوام من 8 مساءً (20:00) إلى 12 منتصف الليل (00:00)، النطاق 4 متر
$stmt = db()->prepare("UPDATE branches SET 
    work_start_time = '20:00:00',
    work_end_time = '00:00:00',
    check_in_start_time = '19:30:00',
    check_in_end_time = '21:30:00',
    check_out_start_time = '23:00:00',
    check_out_end_time = '01:00:00',
    checkout_show_before = 30,
    geofence_radius = 4
");
$stmt->execute();

$affected = $stmt->rowCount();
echo "✅ تم تحديث $affected فرع/فروع\n";

// تحديث الإعدادات الافتراضية أيضاً
$defaults = [
    'work_start_time' => '20:00',
    'work_end_time' => '00:00',
    'check_in_start_time' => '19:30',
    'check_in_end_time' => '21:30',
    'check_out_start_time' => '23:00',
    'check_out_end_time' => '01:00',
    'checkout_show_before' => '30',
    'geofence_radius' => '4'
];

foreach($defaults as $key => $val) {
    $s = db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $s->execute([$key, $val]);
}
echo "✅ تم تحديث الإعدادات الافتراضية\n";

// التحقق من التطبيق
echo "\n=== التحقق من الفروع ===\n";
$s = db()->query("SELECT id, name, work_start_time, work_end_time, geofence_radius FROM branches ORDER BY id");
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf(
        "الفرع #%d: %s | الدوام: %s - %s | النطاق: %d م\n",
        $r['id'],
        $r['name'],
        $r['work_start_time'],
        $r['work_end_time'],
        $r['geofence_radius']
    );
}

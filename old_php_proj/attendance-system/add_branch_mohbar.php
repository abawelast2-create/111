<?php
// =============================================================
// add_branch_mohbar.php — سكريبت لمرة واحدة لإضافة فرع الدهانات والبوية + موبار
// احذف هذا الملف بعد التنفيذ
// =============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$name          = 'الدهانات والبوية + موبار';
$lat           = 24.569472108456402;
$lon           = 46.61440423213129;
$radius        = 25;
$workStart     = '12:00';
$workEnd       = '00:00';
$ciStart       = '11:30';
$ciEnd         = '13:00';
$coStart       = '23:45';
$coEnd         = '00:30';
$coShowBefore  = 15;
$allowOT       = 1;
$otAfter       = 30;
$otMin         = 30;

try {
    // تحقق إذا كان الفرع موجوداً مسبقاً
    $check = db()->prepare("SELECT id FROM branches WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        echo '<div style="font-family:sans-serif;direction:rtl;padding:30px;background:#FEF3C7;border:2px solid #F59E0B;border-radius:10px;max-width:500px;margin:40px auto">';
        echo '<h2>⚠️ الفرع موجود مسبقاً</h2>';
        echo '<p>فرع <strong>' . htmlspecialchars($name) . '</strong> موجود بالفعل في قاعدة البيانات.</p>';
        echo '</div>';
        exit;
    }

    $stmt = db()->prepare("INSERT INTO branches
        (name, latitude, longitude, geofence_radius,
         work_start_time, work_end_time,
         check_in_start_time, check_in_end_time,
         check_out_start_time, check_out_end_time,
         checkout_show_before, allow_overtime,
         overtime_start_after, overtime_min_duration, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)");

    $stmt->execute([
        $name, $lat, $lon, $radius,
        $workStart, $workEnd,
        $ciStart, $ciEnd,
        $coStart, $coEnd,
        $coShowBefore, $allowOT,
        $otAfter, $otMin
    ]);

    $newId = db()->lastInsertId();

    echo '<div style="font-family:sans-serif;direction:rtl;padding:30px;background:#D1FAE5;border:2px solid #10B981;border-radius:10px;max-width:500px;margin:40px auto">';
    echo '<h2>✅ تم إضافة الفرع بنجاح</h2>';
    echo '<p><strong>الاسم:</strong> ' . htmlspecialchars($name) . '</p>';
    echo '<p><strong>الرقم التسلسلي:</strong> ' . $newId . '</p>';
    echo '<p><strong>الإحداثيات:</strong> ' . $lat . ' , ' . $lon . '</p>';
    echo '<p><strong>وقت الدوام:</strong> ' . $workStart . ' – ' . $workEnd . '</p>';
    echo '<p style="margin-top:20px;color:#065F46;font-weight:bold">⚠️ احذف هذا الملف من السيرفر الآن!</p>';
    echo '<p><a href="admin/branches.php" style="background:#10B981;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none">← عرض الفروع</a></p>';
    echo '</div>';

} catch (PDOException $e) {
    echo '<div style="font-family:sans-serif;direction:rtl;padding:30px;background:#FEE2E2;border:2px solid #EF4444;border-radius:10px;max-width:500px;margin:40px auto">';
    echo '<h2>❌ خطأ في الإضافة</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '</div>';
}
?>

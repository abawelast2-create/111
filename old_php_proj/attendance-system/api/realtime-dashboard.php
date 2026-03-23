<?php
// =============================================================
// api/realtime-dashboard.php - بيانات لوحة التحكم بالوقت الفعلي
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// تحقق من تسجيل الدخول عبر الجلسة
if (empty($_SESSION['admin_id'])) {
    jsonResponse(['success' => false, 'message' => 'غير مصرح'], 401);
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

$today = date('Y-m-d');

// إحصائيات اليوم
$stats = getTodayStats();

// عدد الفروع النشطة
$branchCount = (int)db()->query("SELECT COUNT(*) FROM branches WHERE is_active = 1")->fetchColumn();

// آخر 15 تسجيل
$recentStmt = db()->prepare("
    SELECT a.type, a.timestamp, a.latitude, a.longitude,
           e.name AS employee_name, e.job_title,
           b.name AS branch_name
    FROM attendances a
    JOIN employees e ON a.employee_id = e.id
    LEFT JOIN branches b ON e.branch_id = b.id
    WHERE e.is_active = 1 AND e.deleted_at IS NULL
    ORDER BY a.timestamp DESC
    LIMIT 15
");
$recentStmt->execute();
$recentRecords = $recentStmt->fetchAll();

// تنسيق السجلات
$records = [];
foreach ($recentRecords as $rec) {
    $records[] = [
        'employee_name' => $rec['employee_name'],
        'job_title'     => $rec['job_title'],
        'branch_name'   => $rec['branch_name'] ?? '-',
        'type'          => $rec['type'],
        'time'          => date('h:i A', strtotime($rec['timestamp'])),
        'timestamp'     => $rec['timestamp'],
    ];
}

// غائبون اليوم
$absentStmt = db()->prepare("
    SELECT e.name, e.job_title, b.name AS branch_name FROM employees e
    LEFT JOIN branches b ON e.branch_id = b.id
    WHERE e.is_active = 1 AND e.deleted_at IS NULL
      AND e.id NOT IN (
          SELECT DISTINCT employee_id FROM attendances
          WHERE attendance_date = ?
      )
    ORDER BY e.name
    LIMIT 10
");
$absentStmt->execute([$today]);
$absentList = $absentStmt->fetchAll();

$absents = [];
foreach ($absentList as $emp) {
    $absents[] = [
        'name'        => $emp['name'],
        'job_title'   => $emp['job_title'],
        'branch_name' => $emp['branch_name'] ?? '-',
    ];
}

jsonResponse([
    'success'        => true,
    'timestamp'      => date('Y-m-d H:i:s'),
    'stats'          => [
        'branches'       => $branchCount,
        'total_employees'=> (int)$stats['total_employees'],
        'checked_in'     => (int)$stats['checked_in'],
        'checked_out'    => (int)$stats['checked_out'],
        'absent'         => (int)$stats['total_employees'] - (int)$stats['checked_in'],
    ],
    'recent_records' => $records,
    'absent_list'    => $absents,
    'absent_count'   => count($absentList),
]);

<?php
// =============================================================
// api/realtime-attendance.php - بيانات سجلات الحضور بالوقت الفعلي
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

// =================== فلاتر ===================
$dateFrom    = $_GET['date_from'] ?? date('Y-m-d');
$dateTo      = $_GET['date_to']   ?? date('Y-m-d');
$empId       = (int)($_GET['emp_id'] ?? 0);
$type        = $_GET['type'] ?? '';
$filterBranch= (int)($_GET['branch'] ?? 0);
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 25;
$offset      = ($page - 1) * $perPage;

// بناء الاستعلام مع الفلاتر
$where  = ["a.attendance_date BETWEEN ? AND ?"];
$params = [$dateFrom, $dateTo];

if ($empId > 0) { $where[] = "a.employee_id = ?"; $params[] = $empId; }
if (in_array($type, ['in','out'])) { $where[] = "a.type = ?"; $params[] = $type; }
if ($filterBranch > 0) { $where[] = "e.branch_id = ?"; $params[] = $filterBranch; }

$whereStr = implode(' AND ', $where);

// العدد الكلي
$totalStmt = db()->prepare("SELECT COUNT(*) FROM attendances a JOIN employees e ON a.employee_id=e.id WHERE $whereStr");
$totalStmt->execute($params);
$total      = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

// النتائج
$recStmt = db()->prepare("
    SELECT a.*, e.name AS employee_name, e.job_title, b.name AS branch_name
    FROM attendances a
    JOIN employees e ON a.employee_id = e.id
    LEFT JOIN branches b ON e.branch_id = b.id
    WHERE $whereStr
    ORDER BY a.timestamp DESC
    LIMIT ? OFFSET ?
");
$recStmt->execute(array_merge($params, [$perPage, $offset]));
$records = $recStmt->fetchAll();

// إحصائيات الفترة
$statsStmt = db()->prepare("
    SELECT
        COUNT(CASE WHEN type='in' THEN 1 END)  AS total_in,
        COUNT(CASE WHEN type='out' THEN 1 END) AS total_out,
        COUNT(DISTINCT employee_id)             AS unique_employees,
        COUNT(DISTINCT attendance_date)          AS working_days
    FROM attendances a WHERE $whereStr
");
$statsStmt->execute($params);
$periodStats = $statsStmt->fetch();

// تنسيق السجلات
$formatted = [];
foreach ($records as $i => $rec) {
    $formatted[] = [
        'index'         => $offset + $i + 1,
        'employee_name' => $rec['employee_name'],
        'job_title'     => $rec['job_title'],
        'branch_name'   => $rec['branch_name'] ?? '-',
        'type'          => $rec['type'],
        'date'          => date('Y/m/d', strtotime($rec['timestamp'])),
        'time'          => date('h:i A', strtotime($rec['timestamp'])),
        'latitude'      => $rec['latitude'],
        'longitude'     => $rec['longitude'],
        'accuracy'      => $rec['location_accuracy'] ? '±' . round($rec['location_accuracy']) . 'م' : '-',
        'timestamp'     => $rec['timestamp'],
    ];
}

jsonResponse([
    'success'     => true,
    'timestamp'   => date('Y-m-d H:i:s'),
    'stats'       => [
        'total'            => $total,
        'total_in'         => (int)$periodStats['total_in'],
        'total_out'        => (int)$periodStats['total_out'],
        'unique_employees' => (int)$periodStats['unique_employees'],
        'working_days'     => (int)$periodStats['working_days'],
    ],
    'records'     => $formatted,
    'pagination'  => [
        'page'        => $page,
        'per_page'    => $perPage,
        'total'       => $total,
        'total_pages' => $totalPages,
    ],
]);

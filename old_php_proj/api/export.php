<?php
// =============================================================
// api/export.php - تصدير التقارير (CSV, Excel, طباعة)
// =============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminLogin();

$format   = $_GET['format'] ?? 'csv';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to'] ?? date('Y-m-d');
$branchId = !empty($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;
$empId    = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo))   $dateTo   = date('Y-m-d');

$filters = [
    'date_from' => $dateFrom,
    'date_to'   => $dateTo,
];
if ($branchId) $filters['branch_id'] = $branchId;
if ($empId)    $filters['employee_id'] = $empId;

$exportService = new \App\Services\ExportService();

$dateSuffix = $dateFrom . '_' . $dateTo;

switch ($format) {
    case 'excel':
        $exportService->exportExcel($filters, "attendance_{$dateSuffix}.xls");
        break;
    case 'print':
        $exportService->exportAttendancePrintable($filters, 'تقرير الحضور والانصراف');
        break;
    case 'json':
        $exportService->exportJson($filters, "attendance_{$dateSuffix}.json");
        break;
    case 'csv':
    default:
        $exportService->exportCsv($filters, "attendance_{$dateSuffix}.csv");
        break;
}

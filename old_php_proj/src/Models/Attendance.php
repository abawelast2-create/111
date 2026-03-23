<?php
// =============================================================
// src/Models/Attendance.php - نموذج الحضور والانصراف
// =============================================================

namespace App\Models;

use App\Core\Model;

class Attendance extends Model
{
    protected string $table = 'attendances';

    /**
     * آخر سجل للموظف اليوم
     */
    public function getLastTodayRecord(int $employeeId): ?array
    {
        return $this->query(
            "SELECT * FROM attendances
             WHERE employee_id = ? AND attendance_date = CURDATE()
             ORDER BY timestamp DESC LIMIT 1",
            [$employeeId]
        )->fetch() ?: null;
    }

    /**
     * سجلات الموظف اليوم
     */
    public function getTodayRecords(int $employeeId): array
    {
        return $this->query(
            "SELECT * FROM attendances
             WHERE employee_id = ? AND attendance_date = CURDATE()
             ORDER BY timestamp DESC",
            [$employeeId]
        )->fetchAll();
    }

    /**
     * التحقق من وجود سجل حديث (لمنع التكرار)
     */
    public function hasRecentRecord(int $employeeId, string $type, int $minutes = 5): bool
    {
        return (bool)$this->query(
            "SELECT id FROM attendances
             WHERE employee_id = ? AND type = ?
               AND timestamp >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
             LIMIT 1",
            [$employeeId, $type, $minutes]
        )->fetch();
    }

    /**
     * إحصائيات اليوم
     */
    public function getTodayStats(): array
    {
        $today = date('Y-m-d');
        return $this->query(
            "SELECT
                (SELECT COUNT(DISTINCT employee_id) FROM attendances WHERE attendance_date = ? AND type = 'in') AS checked_in,
                (SELECT COUNT(DISTINCT employee_id) FROM attendances WHERE attendance_date = ? AND type = 'out') AS checked_out,
                (SELECT COUNT(*) FROM employees WHERE is_active = 1 AND deleted_at IS NULL) AS total_employees",
            [$today, $today]
        )->fetch();
    }

    /**
     * تقرير الحضور مع تصفية وصفحات
     */
    public function getFilteredReport(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'a.attendance_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'a.attendance_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['employee_id'])) {
            $where[] = 'a.employee_id = ?';
            $params[] = $filters['employee_id'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'a.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['branch_id'])) {
            $where[] = 'e.branch_id = ?';
            $params[] = $filters['branch_id'];
        }

        $whereStr = implode(' AND ', $where);

        // Count total
        $totalStmt = $this->query(
            "SELECT COUNT(*) FROM attendances a
             JOIN employees e ON a.employee_id = e.id
             WHERE {$whereStr}",
            $params
        );
        $total = (int)$totalStmt->fetchColumn();

        // Get records with cursor-safe pagination
        $offset = ($page - 1) * $perPage;
        $records = $this->query(
            "SELECT a.*, e.name AS employee_name, e.job_title, b.name AS branch_name
             FROM attendances a
             JOIN employees e ON a.employee_id = e.id
             LEFT JOIN branches b ON e.branch_id = b.id
             WHERE {$whereStr}
             ORDER BY a.timestamp DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();

        return [
            'data' => $records,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * السجلات الأخيرة
     */
    public function getRecent(int $limit = 15): array
    {
        return $this->query(
            "SELECT a.*, e.name AS employee_name, b.name AS branch_name
             FROM attendances a
             JOIN employees e ON a.employee_id = e.id
             LEFT JOIN branches b ON e.branch_id = b.id
             WHERE a.attendance_date = CURDATE()
             ORDER BY a.timestamp DESC
             LIMIT ?",
            [$limit]
        )->fetchAll();
    }

    /**
     * إحصائيات الفترة للتقارير
     */
    public function getPeriodStats(string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $params = [$dateFrom, $dateTo, $dateFrom, $dateTo];
        $branchFilter = '';
        if ($branchId) {
            $branchFilter = 'AND e.branch_id = ?';
            $params[] = $branchId;
        }

        return $this->query(
            "SELECT
                COUNT(CASE WHEN a.type = 'in' THEN 1 END) AS total_check_ins,
                COUNT(CASE WHEN a.type = 'out' THEN 1 END) AS total_check_outs,
                COUNT(DISTINCT a.employee_id) AS unique_employees,
                COUNT(DISTINCT a.attendance_date) AS working_days,
                AVG(CASE WHEN a.type = 'in' THEN a.late_minutes END) AS avg_late_minutes,
                SUM(CASE WHEN a.type = 'in' AND a.late_minutes > 0 THEN 1 ELSE 0 END) AS late_count
             FROM attendances a
             JOIN employees e ON a.employee_id = e.id
             WHERE a.attendance_date BETWEEN ? AND ?
               AND a.type IN ('in', 'out')
               {$branchFilter}",
            $params
        )->fetch();
    }

    /**
     * تقرير التأخير
     */
    public function getLateReport(string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $params = [$dateFrom, $dateTo];
        $branchFilter = '';
        if ($branchId) {
            $branchFilter = 'AND e.branch_id = ?';
            $params[] = $branchId;
        }

        return $this->query(
            "SELECT e.id, e.name, e.job_title, b.name AS branch_name,
                    COUNT(*) AS late_days,
                    SUM(a.late_minutes) AS total_late_minutes,
                    AVG(a.late_minutes) AS avg_late_minutes,
                    MAX(a.late_minutes) AS max_late_minutes
             FROM attendances a
             JOIN employees e ON a.employee_id = e.id
             LEFT JOIN branches b ON e.branch_id = b.id
             WHERE a.type = 'in' AND a.late_minutes > 0
               AND a.attendance_date BETWEEN ? AND ?
               {$branchFilter}
             GROUP BY e.id
             ORDER BY total_late_minutes DESC",
            $params
        )->fetchAll();
    }
}

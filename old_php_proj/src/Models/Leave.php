<?php
// =============================================================
// src/Models/Leave.php - نموذج الإجازات
// =============================================================

namespace App\Models;

use App\Core\Model;

class Leave extends Model
{
    protected string $table = 'leaves';

    /**
     * إجازات موظف
     */
    public function getByEmployee(int $employeeId, ?string $status = null): array
    {
        $sql = "SELECT l.*, a.full_name AS approved_by_name
                FROM leaves l
                LEFT JOIN admins a ON l.approved_by = a.id
                WHERE l.employee_id = ?";
        $params = [$employeeId];

        if ($status) {
            $sql .= " AND l.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY l.start_date DESC";
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * الإجازات المعلقة
     */
    public function getPending(): array
    {
        return $this->query(
            "SELECT l.*, e.name AS employee_name, e.job_title, b.name AS branch_name
             FROM leaves l
             JOIN employees e ON l.employee_id = e.id
             LEFT JOIN branches b ON e.branch_id = b.id
             WHERE l.status = 'pending'
             ORDER BY l.created_at DESC"
        )->fetchAll();
    }

    /**
     * التحقق من تعارض الإجازات
     */
    public function hasOverlapping(int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM leaves
                WHERE employee_id = ? AND status != 'rejected'
                  AND start_date <= ? AND end_date >= ?";
        $params = [$employeeId, $endDate, $startDate];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        return (bool)$this->query($sql, $params)->fetch();
    }

    /**
     * الموافقة على إجازة
     */
    public function approve(int $id, int $adminId): bool
    {
        return $this->update($id, [
            'status' => 'approved',
            'approved_by' => $adminId,
        ]);
    }

    /**
     * رفض إجازة
     */
    public function reject(int $id, int $adminId): bool
    {
        return $this->update($id, [
            'status' => 'rejected',
            'approved_by' => $adminId,
        ]);
    }

    /**
     * هل الموظف في إجازة اليوم؟
     */
    public function isOnLeaveToday(int $employeeId): bool
    {
        $today = date('Y-m-d');
        return (bool)$this->query(
            "SELECT id FROM leaves
             WHERE employee_id = ? AND status = 'approved'
               AND ? BETWEEN start_date AND end_date
             LIMIT 1",
            [$employeeId, $today]
        )->fetch();
    }
}

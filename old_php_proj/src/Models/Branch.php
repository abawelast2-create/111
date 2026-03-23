<?php
// =============================================================
// src/Models/Branch.php - نموذج الفرع
// =============================================================

namespace App\Models;

use App\Core\Model;

class Branch extends Model
{
    protected string $table = 'branches';

    /**
     * جلب الفروع النشطة
     */
    public function getActive(): array
    {
        return $this->all(['is_active' => 1], 'name ASC');
    }

    /**
     * جلب فرع مع عدد الموظفين
     */
    public function getWithEmployeeCount(): array
    {
        return $this->query(
            "SELECT b.*, COUNT(e.id) AS employee_count
             FROM branches b
             LEFT JOIN employees e ON e.branch_id = b.id AND e.is_active = 1 AND e.deleted_at IS NULL
             GROUP BY b.id
             ORDER BY b.name"
        )->fetchAll();
    }

    /**
     * جلب إعدادات الجدول الزمني للفرع
     */
    public function getSchedule(int $branchId): ?array
    {
        return $this->query(
            "SELECT work_start_time, work_end_time, check_in_start_time, check_in_end_time,
                    check_out_start_time, check_out_end_time, checkout_show_before,
                    allow_overtime, overtime_start_after, overtime_min_duration,
                    latitude, longitude, geofence_radius
             FROM branches WHERE id = ? AND is_active = 1",
            [$branchId]
        )->fetch() ?: null;
    }
}

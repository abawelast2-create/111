<?php
// =============================================================
// src/Models/Employee.php - نموذج الموظف
// =============================================================

namespace App\Models;

use App\Core\Model;

class Employee extends Model
{
    protected string $table = 'employees';

    /**
     * البحث بالتوكن
     */
    public function findByToken(string $token): ?array
    {
        return $this->findWhere([
            'unique_token' => $token,
            'is_active' => 1,
            'deleted_at' => null,
        ]);
    }

    /**
     * البحث بالـ PIN
     */
    public function findByPin(string $pin): ?array
    {
        return $this->findWhere([
            'pin' => $pin,
            'is_active' => 1,
            'deleted_at' => null,
        ]);
    }

    /**
     * جلب موظفي فرع معين
     */
    public function getByBranch(int $branchId): array
    {
        return $this->query(
            "SELECT * FROM employees WHERE branch_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY name",
            [$branchId]
        )->fetchAll();
    }

    /**
     * جلب الموظفين النشطين مع اسم الفرع
     */
    public function getAllActive(): array
    {
        return $this->query(
            "SELECT e.*, b.name AS branch_name
             FROM employees e
             LEFT JOIN branches b ON e.branch_id = b.id
             WHERE e.is_active = 1 AND e.deleted_at IS NULL
             ORDER BY b.name, e.name"
        )->fetchAll();
    }

    /**
     * حذف ناعم
     */
    public function softDelete(int $id): bool
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * استعادة محذوف
     */
    public function restore(int $id): bool
    {
        return $this->update($id, ['deleted_at' => null]);
    }

    /**
     * توليد توكن فريد
     */
    public function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
            $existing = $this->findWhere(['unique_token' => $token]);
        } while ($existing);
        return $token;
    }

    /**
     * توليد PIN فريد
     */
    public function generateUniquePin(): string
    {
        do {
            $pin = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $existing = $this->query(
                "SELECT id FROM employees WHERE pin = ?", [$pin]
            )->fetch();
        } while ($existing);
        return $pin;
    }

    /**
     * الموظفون الغائبون اليوم
     */
    public function getAbsentToday(int $limit = 10): array
    {
        $today = date('Y-m-d');
        return $this->query(
            "SELECT e.id, e.name, e.job_title, b.name AS branch_name
             FROM employees e
             LEFT JOIN branches b ON e.branch_id = b.id
             WHERE e.is_active = 1 AND e.deleted_at IS NULL
               AND e.id NOT IN (
                   SELECT DISTINCT employee_id FROM attendances
                   WHERE attendance_date = ? AND type = 'in'
               )
             ORDER BY e.name
             LIMIT ?",
            [$today, $limit]
        )->fetchAll();
    }

    /**
     * بحث بالاسم أو المسمى أو PIN
     */
    public function search(string $query, ?int $branchId = null): array
    {
        $searchTerm = "%{$query}%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
        $sql = "SELECT e.*, b.name AS branch_name
                FROM employees e
                LEFT JOIN branches b ON e.branch_id = b.id
                WHERE e.deleted_at IS NULL
                  AND (e.name LIKE ? OR e.job_title LIKE ? OR e.pin LIKE ?)";

        if ($branchId !== null) {
            $sql .= " AND e.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY e.name";
        return $this->query($sql, $params)->fetchAll();
    }
}

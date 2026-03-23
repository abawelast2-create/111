<?php
// =============================================================
// src/Models/Admin.php - نموذج المدير
// =============================================================

namespace App\Models;

use App\Core\Model;

class Admin extends Model
{
    protected string $table = 'admins';

    /**
     * البحث بالاسم
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findWhere(['username' => $username]);
    }

    /**
     * تحديث آخر تسجيل دخول
     */
    public function updateLastLogin(int $id): void
    {
        $this->update($id, ['last_login' => date('Y-m-d H:i:s')]);
    }

    /**
     * تحديث كلمة المرور
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        return $this->update($id, ['password_hash' => $hashedPassword]);
    }

    /**
     * التحقق من تفعيل 2FA
     */
    public function has2FA(int $id): bool
    {
        $admin = $this->find($id);
        return !empty($admin['two_factor_secret']);
    }
}

<?php
// =============================================================
// src/Services/AuthService.php - خدمة المصادقة
// =============================================================

namespace App\Services;

use App\Models\Admin;

class AuthService
{
    private Admin $admin;

    public function __construct()
    {
        $this->admin = new Admin();
    }

    /**
     * تسجيل دخول المدير
     */
    public function login(string $username, string $password, bool $remember = false): array
    {
        $admin = $this->admin->findByUsername(trim($username));

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return ['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'];
        }

        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['last_activity'] = time();

        if ($remember) {
            $ttl = 2592000; // 30 يوم
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie('remember_admin', '1', [
                'expires' => time() + $ttl,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            ini_set('session.cookie_lifetime', $ttl);
            ini_set('session.gc_maxlifetime', $ttl);
        }

        $this->admin->updateLastLogin($admin['id']);

        return ['success' => true, 'message' => 'تم تسجيل الدخول بنجاح'];
    }

    /**
     * تسجيل الخروج
     */
    public function logout(): void
    {
        $_SESSION = [];
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('remember_admin', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_destroy();
    }

    /**
     * فحص الجلسة مع مهلة الخمول
     */
    public function checkSession(int $timeoutMinutes = 30): bool
    {
        if (empty($_SESSION['admin_id'])) {
            return false;
        }

        // التحقق من مهلة الخمول
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $timeout = $timeoutMinutes * 60;

        if ((time() - $lastActivity) > $timeout) {
            // "تذكرني" يتجاوز المهلة
            if (!empty($_COOKIE['remember_admin']) && $_COOKIE['remember_admin'] === '1') {
                $_SESSION['last_activity'] = time();
                return true;
            }
            $this->logout();
            return false;
        }

        // تجديد وقت النشاط
        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * هل المدير مسجل دخوله؟
     */
    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    /**
     * التحقق من محاولات الدخول (حماية brute-force)
     */
    public function checkLoginAttempts(string $ip, int $maxAttempts = 5, int $windowMinutes = 10): bool
    {
        $db = \App\Core\Database::getInstance();

        // تنظيف المحاولات القديمة
        $db->prepare(
            "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        )->execute([$windowMinutes]);

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$ip, $windowMinutes]);
        $count = (int)$stmt->fetchColumn();

        return $count < $maxAttempts;
    }

    /**
     * تسجيل محاولة دخول فاشلة
     */
    public function recordFailedAttempt(string $ip, string $username): void
    {
        $db = \App\Core\Database::getInstance();
        $db->prepare(
            "INSERT INTO login_attempts (ip_address, username, attempted_at) VALUES (?, ?, NOW())"
        )->execute([$ip, $username]);
    }

    /**
     * تغيير كلمة المرور
     */
    public function changePassword(int $adminId, string $currentPassword, string $newPassword): array
    {
        $admin = $this->admin->find($adminId);
        if (!$admin) {
            return ['success' => false, 'message' => 'المدير غير موجود'];
        }

        if (!password_verify($currentPassword, $admin['password_hash'])) {
            return ['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->admin->updatePassword($adminId, $hash);

        return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
    }
}

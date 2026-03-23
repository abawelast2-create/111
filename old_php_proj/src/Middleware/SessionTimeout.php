<?php
// =============================================================
// src/Middleware/SessionTimeout.php - مهلة انتهاء الجلسة التلقائية
// =============================================================

namespace App\Middleware;

class SessionTimeout
{
    private int $timeoutMinutes;

    public function __construct(int $timeoutMinutes = 30)
    {
        $this->timeoutMinutes = $timeoutMinutes;
    }

    /**
     * فحص وتجديد الجلسة
     * @return bool true إذا كانت الجلسة صالحة
     */
    public function check(): bool
    {
        if (empty($_SESSION['admin_id'])) {
            return false;
        }

        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $timeout = $this->timeoutMinutes * 60;

        if ((time() - $lastActivity) > $timeout) {
            // تذكرني يتجاوز المهلة
            if (!empty($_COOKIE['remember_admin']) && $_COOKIE['remember_admin'] === '1') {
                $_SESSION['last_activity'] = time();
                return true;
            }

            // انتهاء الجلسة - تسجيل خروج
            $this->expireSession();
            return false;
        }

        // تجديد وقت النشاط
        $_SESSION['last_activity'] = time();

        // تجديد معرف الجلسة كل 5 دقائق لحماية من Session Fixation
        if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }

        return true;
    }

    /**
     * إنهاء الجلسة
     */
    private function expireSession(): void
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

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * الحصول على الوقت المتبقي قبل انتهاء الجلسة (بالثواني)
     */
    public function getRemainingTime(): int
    {
        if (empty($_SESSION['last_activity'])) return 0;
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = ($this->timeoutMinutes * 60) - $elapsed;
        return max(0, $remaining);
    }
}

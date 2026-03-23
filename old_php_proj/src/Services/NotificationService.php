<?php
// =============================================================
// src/Services/NotificationService.php - خدمة الإشعارات
// =============================================================

namespace App\Services;

use App\Core\Database;

class NotificationService
{
    /**
     * إنشاء إشعار
     */
    public function create(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO notifications (employee_id, admin_id, type, title, message, data_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['employee_id'] ?? null,
            $data['admin_id'] ?? null,
            $data['type'],
            $data['title'],
            $data['message'] ?? null,
            !empty($data['data']) ? json_encode($data['data'], JSON_UNESCAPED_UNICODE) : null,
        ]);
        return (int)$db->lastInsertId();
    }

    /**
     * إشعار تسجيل من خارج النطاق
     */
    public function notifyOutOfRange(int $employeeId, string $employeeName, float $distance, float $radius): void
    {
        $this->create([
            'employee_id' => $employeeId,
            'type' => 'out_of_range',
            'title' => 'تسجيل من خارج النطاق',
            'message' => "محاولة تسجيل من {$employeeName} على بعد " . round($distance) . " متر (الحد: {$radius} م)",
            'data' => ['distance' => $distance, 'radius' => $radius],
        ]);
    }

    /**
     * إشعار جهاز غير معروف
     */
    public function notifyUnknownDevice(int $employeeId, string $employeeName): void
    {
        $this->create([
            'employee_id' => $employeeId,
            'type' => 'unknown_device',
            'title' => 'جهاز غير معروف',
            'message' => "محاولة تسجيل من {$employeeName} باستخدام جهاز غير مسجل",
        ]);
    }

    /**
     * إشعار حالة تلاعب
     */
    public function notifyTampering(int $employeeId, string $employeeName, string $caseType): void
    {
        $this->create([
            'employee_id' => $employeeId,
            'type' => 'tampering',
            'title' => 'اكتشاف تلاعب محتمل',
            'message' => "اكتشاف حالة {$caseType} للموظف {$employeeName}",
        ]);
    }

    /**
     * جلب إشعارات المشرف غير المقروءة
     */
    public function getUnread(int $limit = 20): array
    {
        $db = Database::getInstance();
        return $db->prepare("
            SELECT n.*, e.name AS employee_name
            FROM notifications n
            LEFT JOIN employees e ON n.employee_id = e.id
            WHERE n.is_read = 0 AND n.admin_id IS NULL
            ORDER BY n.created_at DESC
            LIMIT ?
        ")->execute([$limit]) ? $db->prepare("
            SELECT n.*, e.name AS employee_name
            FROM notifications n
            LEFT JOIN employees e ON n.employee_id = e.id
            WHERE n.is_read = 0 AND n.admin_id IS NULL
            ORDER BY n.created_at DESC
            LIMIT {$limit}
        ")->fetchAll() : [];
    }

    /**
     * جلب الإشعارات غير المقروءة (بشكل مبسط)
     */
    public function getUnreadNotifications(int $limit = 20): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT n.*, e.name AS employee_name
            FROM notifications n
            LEFT JOIN employees e ON n.employee_id = e.id
            WHERE n.is_read = 0
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * تعليم إشعار كمقروء
     */
    public function markAsRead(int $id): void
    {
        $db = Database::getInstance();
        $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?")
            ->execute([$id]);
    }

    /**
     * تعليم الكل كمقروء
     */
    public function markAllAsRead(): void
    {
        $db = Database::getInstance();
        $db->exec("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
    }

    /**
     * عدد غير المقروءة
     */
    public function unreadCount(): int
    {
        $db = Database::getInstance();
        return (int)$db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
    }
}

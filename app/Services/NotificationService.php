<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Employee;
use App\Models\Admin;

class NotificationService
{
    /**
     * إرسال إشعار لموظف
     */
    public static function notifyEmployee(int $employeeId, string $type, string $title, string $body, array $data = []): Notification
    {
        return Notification::create([
            'type'            => $type,
            'notifiable_type' => Employee::class,
            'notifiable_id'   => $employeeId,
            'title'           => $title,
            'body'            => $body,
            'data'            => $data,
            'channel'         => 'database',
            'sent_at'         => now(),
        ]);
    }

    /**
     * إرسال إشعار لمدير
     */
    public static function notifyAdmin(int $adminId, string $type, string $title, string $body, array $data = []): Notification
    {
        return Notification::create([
            'type'            => $type,
            'notifiable_type' => Admin::class,
            'notifiable_id'   => $adminId,
            'title'           => $title,
            'body'            => $body,
            'data'            => $data,
            'channel'         => 'database',
            'sent_at'         => now(),
        ]);
    }

    /**
     * إرسال إشعار لجميع المديرين
     */
    public static function notifyAllAdmins(string $type, string $title, string $body, array $data = []): void
    {
        Admin::all()->each(function ($admin) use ($type, $title, $body, $data) {
            self::notifyAdmin($admin->id, $type, $title, $body, $data);
        });
    }

    /**
     * إشعار تأكيد تسجيل الحضور
     */
    public static function sendCheckinConfirmation(Employee $employee, int $lateMinutes = 0): void
    {
        $message = $lateMinutes > 0
            ? "تم تسجيل حضورك بتأخر {$lateMinutes} دقيقة"
            : 'تم تسجيل حضورك بنجاح';

        self::notifyEmployee($employee->id, 'checkin_confirm', 'تأكيد الحضور', $message, [
            'late_minutes' => $lateMinutes,
            'time'        => now()->format('H:i'),
        ]);
    }

    /**
     * إشعار تأكيد تسجيل الانصراف
     */
    public static function sendCheckoutConfirmation(Employee $employee): void
    {
        self::notifyEmployee($employee->id, 'checkout_confirm', 'تأكيد الانصراف', 'تم تسجيل انصرافك بنجاح', [
            'time' => now()->format('H:i'),
        ]);
    }

    /**
     * تنبيه الخروج من النطاق الجغرافي
     */
    public static function sendGeofenceAlert(Employee $employee, float $distance): void
    {
        self::notifyEmployee($employee->id, 'geofence_alert', 'تنبيه الموقع', "تم رصد خروجك من نطاق العمل. المسافة: {$distance} متر");

        // إبلاغ المديرين أيضاً
        self::notifyAllAdmins('geofence_alert', 'تنبيه خروج من النطاق', "الموظف {$employee->name} خارج نطاق العمل ({$distance}m)", [
            'employee_id'   => $employee->id,
            'employee_name' => $employee->name,
            'distance'      => $distance,
        ]);
    }

    /**
     * إشعار بلاغ سري جديد
     */
    public static function sendNewReportAlert(int $reportId): void
    {
        self::notifyAllAdmins('report_alert', 'بلاغ سري جديد', 'تم تقديم بلاغ سري جديد يحتاج مراجعة', [
            'report_id' => $reportId,
        ]);
    }

    /**
     * إشعار اكتشاف تلاعب
     */
    public static function sendTamperingAlert(Employee $employee, string $caseType): void
    {
        self::notifyAllAdmins('tampering_alert', 'اكتشاف تلاعب', "تم رصد حالة تلاعب ({$caseType}) للموظف {$employee->name}", [
            'employee_id'   => $employee->id,
            'employee_name' => $employee->name,
            'case_type'     => $caseType,
        ]);
    }

    /**
     * الحصول على إشعارات غير مقروءة
     */
    public static function getUnreadCount(string $type, int $id): int
    {
        return Notification::forUser($type, $id)->unread()->count();
    }

    /**
     * الحصول على الإشعارات الأخيرة
     */
    public static function getRecent(string $type, int $id, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::forUser($type, $id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * تحديد كل الإشعارات كمقروءة
     */
    public static function markAllRead(string $type, int $id): void
    {
        Notification::forUser($type, $id)->unread()->update(['read_at' => now()]);
    }
}

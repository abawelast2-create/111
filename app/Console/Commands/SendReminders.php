<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Admin;
use App\Services\NotificationService;
use App\Services\ScheduleService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $description = 'إرسال تذكيرات الحضور والتقارير اليومية';

    public function handle(): int
    {
        $this->sendAttendanceReminders();
        $this->sendDailySummary();

        $this->info('تم إرسال التذكيرات بنجاح');
        return Command::SUCCESS;
    }

    /**
     * تذكير الموظفين قبل 15 دقيقة من نهاية نافذة الحضور
     */
    private function sendAttendanceReminders(): void
    {
        $employees = Employee::active()->with('branch')->get();
        $today = today()->toDateString();

        foreach ($employees as $employee) {
            $schedule = ScheduleService::getBranchSchedule($employee->branch_id);
            $checkInEnd = $schedule['check_in_end_time'];

            // حساب الوقت المتبقي
            $endTime = strtotime($today . ' ' . $checkInEnd);
            $reminderTime = $endTime - (15 * 60); // 15 دقيقة قبل

            if (abs(time() - $reminderTime) > 120) { // نافذة ±2 دقيقة
                continue;
            }

            // تحقق: هل سجّل حضور اليوم؟
            $hasCheckedIn = Attendance::where('employee_id', $employee->id)
                ->where('attendance_date', $today)
                ->where('type', 'in')
                ->exists();

            if (!$hasCheckedIn) {
                NotificationService::notifyEmployee(
                    $employee->id,
                    'attendance_reminder',
                    'تذكير بالحضور',
                    "تبقى 15 دقيقة على نهاية نافذة الحضور ({$checkInEnd}). لم يتم تسجيل حضورك بعد."
                );
            }
        }
    }

    /**
     * إرسال تقرير يومي ملخص للمديرين
     */
    private function sendDailySummary(): void
    {
        $today = today()->toDateString();
        $now = now()->format('H:i');

        // إرسال الملخص في الساعة 17:00
        if ($now < '16:55' || $now > '17:10') {
            return;
        }

        $stats = Attendance::getTodayStats();
        $lateCount = Attendance::where('attendance_date', $today)
            ->where('type', 'in')
            ->where('late_minutes', '>', 0)
            ->count();

        $absentCount = $stats['total_employees'] - $stats['checked_in'];

        $body = "ملخص اليوم:\n";
        $body .= "• الحاضرون: {$stats['checked_in']}\n";
        $body .= "• المنصرفون: {$stats['checked_out']}\n";
        $body .= "• المتأخرون: {$lateCount}\n";
        $body .= "• الغائبون: {$absentCount}\n";
        $body .= "• إجمالي الموظفين: {$stats['total_employees']}";

        NotificationService::notifyAllAdmins(
            'daily_summary',
            'التقرير اليومي',
            $body,
            [
                'checked_in'  => $stats['checked_in'],
                'checked_out' => $stats['checked_out'],
                'late'        => $lateCount,
                'absent'      => $absentCount,
            ]
        );
    }
}

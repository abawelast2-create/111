<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\Branch;
use App\Services\WebhookService;

class AttendanceService
{
    public static function record(
        int $employeeId, string $type, float $lat, float $lon,
        float $accuracy = 0, bool $mockLocation = false,
        ?float $validationScore = null, ?array $wifiNetworks = null
    ): array {
        $validTypes = ['in', 'out', 'overtime-start', 'overtime-end'];
        if (!in_array($type, $validTypes, true)) {
            return ['success' => false, 'message' => 'نوع تسجيل غير صالح'];
        }

        if (Attendance::hasRecentRecord($employeeId, $type, 5)) {
            return ['success' => false, 'message' => 'تم التسجيل مسبقاً خلال آخر 5 دقائق'];
        }

        $lateMinutes = 0;
        if ($type === 'in') {
            $employee = Employee::find($employeeId);

            // دعم ساعات العمل المرنة
            $flexSchedule = $employee->getFlexibleSchedule();
            if ($flexSchedule) {
                $workStartStr = $flexSchedule['start'];
            } else {
                $schedule = ScheduleService::getBranchSchedule($employee->branch_id ?? null);
                $workStartStr = $schedule['work_start_time'];
            }

            $workStart = strtotime(today()->toDateString() . ' ' . $workStartStr);
            $now = time();

            if ($workStart > $now + 43200) {
                $workStart = strtotime(today()->subDay()->toDateString() . ' ' . $workStartStr);
            }
            if ($now > $workStart) {
                $lateMinutes = max(0, (int) round(($now - $workStart) / 60));
            }
        }

        $attendance = Attendance::create([
            'employee_id'            => $employeeId,
            'type'                   => $type,
            'timestamp'              => now(),
            'attendance_date'        => today()->toDateString(),
            'late_minutes'           => $lateMinutes,
            'latitude'               => $lat,
            'longitude'              => $lon,
            'location_accuracy'      => $accuracy,
            'mock_location_detected' => $mockLocation,
            'validation_score'       => $validationScore,
            'wifi_networks'          => $wifiNetworks,
            'ip_address'             => request()->ip(),
            'user_agent'             => request()->userAgent(),
        ]);

        // إطلاق Webhook
        WebhookService::dispatch(
            $type === 'in' ? 'attendance.checkin' : ($type === 'out' ? 'attendance.checkout' : 'attendance.overtime'),
            [
                'employee_id' => $employeeId,
                'type'        => $type,
                'timestamp'   => now()->toIso8601String(),
                'late_minutes'=> $lateMinutes,
            ]
        );

        $messages = [
            'in'             => 'تم تسجيل الدخول بنجاح',
            'out'            => 'تم تسجيل الانصراف بنجاح',
            'overtime-start' => 'تم بدء الدوام الإضافي',
            'overtime-end'   => 'تم إنهاء الدوام الإضافي',
        ];

        return [
            'success'      => true,
            'message'      => $messages[$type],
            'late_minutes' => $lateMinutes,
        ];
    }

    public static function isWithinTimeWindow(string $nowTime, string $startTime, string $endTime): bool
    {
        if ($endTime < $startTime) {
            return !($nowTime < $startTime && $nowTime > $endTime);
        }
        return $nowTime >= $startTime && $nowTime <= $endTime;
    }
}

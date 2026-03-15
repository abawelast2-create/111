<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use App\Services\ScheduleService;

class AutoCheckout extends Command
{
    protected $signature = 'attendance:auto-checkout';
    protected $description = 'تسجيل انصراف تلقائي للموظفين الذين نسوا تسجيل الانصراف';

    public function handle(): int
    {
        $today = today()->toDateString();
        $now   = now();

        // الموظفون الذين سجلوا دخول بدون انصراف
        $checkedInIds = Attendance::where('attendance_date', $today)
            ->where('type', 'in')
            ->pluck('employee_id')
            ->unique();

        $checkedOutIds = Attendance::where('attendance_date', $today)
            ->where('type', 'out')
            ->pluck('employee_id')
            ->unique();

        $pendingIds = $checkedInIds->diff($checkedOutIds);

        if ($pendingIds->isEmpty()) {
            $this->info('لا يوجد موظفين بحاجة لتسجيل انصراف تلقائي');
            return 0;
        }

        $count = 0;
        foreach ($pendingIds as $employeeId) {
            $employee = Employee::find($employeeId);
            if (!$employee) continue;

            $schedule = ScheduleService::getBranchSchedule($employee->branch_id);
            $coEndTime = $schedule['check_out_end_time'];

            $coEnd = strtotime($today . ' ' . $coEndTime);
            // Handle midnight crossing
            if ($coEnd < strtotime($today . ' ' . $schedule['check_in_start_time'])) {
                $coEnd = strtotime(today()->addDay()->toDateString() . ' ' . $coEndTime);
            }

            if ($now->timestamp >= $coEnd) {
                // Get last check-in location
                $lastCheckin = Attendance::where('employee_id', $employeeId)
                    ->where('attendance_date', $today)
                    ->where('type', 'in')
                    ->latest('timestamp')
                    ->first();

                Attendance::create([
                    'employee_id'     => $employeeId,
                    'type'            => 'out',
                    'timestamp'       => $now,
                    'attendance_date' => $today,
                    'late_minutes'    => 0,
                    'latitude'        => $lastCheckin->latitude ?? 0,
                    'longitude'       => $lastCheckin->longitude ?? 0,
                    'notes'           => 'تسجيل انصراف تلقائي',
                    'ip_address'      => '127.0.0.1',
                ]);

                $count++;
                $this->line("✅ تسجيل انصراف تلقائي: {$employee->name}");
            }
        }

        // إنهاء دوام إضافي مفتوح بعد الساعة 3 صباحاً
        if ((int) $now->format('H') >= 3) {
            $openOT = Attendance::where('attendance_date', $today)
                ->where('type', 'overtime-start')
                ->whereNotIn('employee_id', function ($q) use ($today) {
                    $q->select('employee_id')->from('attendances')
                        ->where('attendance_date', $today)
                        ->where('type', 'overtime-end');
                })
                ->get();

            foreach ($openOT as $ot) {
                Attendance::create([
                    'employee_id'     => $ot->employee_id,
                    'type'            => 'overtime-end',
                    'timestamp'       => $now,
                    'attendance_date' => $today,
                    'late_minutes'    => 0,
                    'latitude'        => $ot->latitude,
                    'longitude'       => $ot->longitude,
                    'notes'           => 'إنهاء دوام إضافي تلقائي',
                    'ip_address'      => '127.0.0.1',
                ]);
                $count++;
                $this->line("✅ إنهاء دوام إضافي تلقائي: الموظف #{$ot->employee_id}");
            }
        }

        $this->info("تم: {$count} عملية تلقائية");
        return 0;
    }
}

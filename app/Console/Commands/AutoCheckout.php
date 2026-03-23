<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\ScheduleService;
use Carbon\Carbon;

class AutoCheckout extends Command
{
    protected $signature = 'attendance:auto-checkout';
    protected $description = 'تسجيل انصراف تلقائي للموظفين الذين نسوا تسجيل الانصراف';

    public function handle(): int
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $now = now();
        $count = 0;

        // الموظفون الذين سجلوا دخول (اليوم أو أمس) بدون انصراف لنفس attendance_date
        $openCheckins = Attendance::query()
            ->from('attendances as ci')
            ->select('ci.*')
            ->where('ci.type', 'in')
            ->whereIn('ci.attendance_date', [$today, $yesterday])
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('employees as e')
                    ->whereColumn('e.id', 'ci.employee_id')
                    ->where('e.is_active', true)
                    ->whereNull('e.deleted_at');
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('attendances as co')
                    ->whereColumn('co.employee_id', 'ci.employee_id')
                    ->whereColumn('co.attendance_date', 'ci.attendance_date')
                    ->where('co.type', 'out');
            })
            ->orderBy('ci.timestamp')
            ->get();

        foreach ($openCheckins as $checkin) {
            $employee = Employee::find($checkin->employee_id);
            if (!$employee) {
                continue;
            }

            $schedule = ScheduleService::getBranchSchedule($employee->branch_id);
            $shift1 = $schedule['shift1'];
            $shift2 = $schedule['shift2'];

            $detectedShift = ScheduleService::detectShiftByCheckinTime(
                $checkin->timestamp->toDateTimeString(),
                $shift1,
                $shift2
            );

            $activeShift = $detectedShift === 2 ? $shift2 : $shift1;
            $coEndTime = $activeShift['check_out_end_time'];
            $ciStartTime = $activeShift['check_in_start_time'];

            $expectedCheckout = Carbon::parse($checkin->attendance_date->toDateString() . ' ' . $coEndTime);
            $checkinAt = $checkin->timestamp instanceof Carbon
                ? $checkin->timestamp->copy()
                : Carbon::parse($checkin->timestamp);

            // إذا وقت الانصراف في نفس اليوم قبل وقت دخول الشفت فهذا شفت عابر لمنتصف الليل
            if (
                ScheduleService::timeToMinutes($coEndTime) <= ScheduleService::timeToMinutes($ciStartTime)
                || $expectedCheckout->lessThanOrEqualTo($checkinAt)
            ) {
                $expectedCheckout->addDay();
            }

            if ($now->greaterThanOrEqualTo($expectedCheckout)) {
                Attendance::create([
                    'employee_id'       => $checkin->employee_id,
                    'type'              => 'out',
                    'timestamp'         => $now,
                    'attendance_date'   => $checkin->attendance_date,
                    'late_minutes'      => 0,
                    'latitude'          => $checkin->latitude ?? 0,
                    'longitude'         => $checkin->longitude ?? 0,
                    'location_accuracy' => 0,
                    'notes'             => 'تسجيل انصراف تلقائي - لم يسجل الموظف',
                    'ip_address'        => 'AUTO',
                    'user_agent'        => 'AUTO-CHECKOUT-COMMAND',
                ]);

                $count++;
                $this->line("✅ تسجيل انصراف تلقائي: {$employee->name} (شفت {$detectedShift})");
            }
        }

        // إنهاء دوام إضافي مفتوح من الأمس بعد 3 صباحاً
        if ((int) $now->format('H') >= 3) {
            $openOT = Attendance::query()
                ->from('attendances as ot_start')
                ->select('ot_start.*')
                ->where('ot_start.attendance_date', $yesterday)
                ->where('ot_start.type', 'overtime-start')
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('attendances as ot_end')
                        ->whereColumn('ot_end.employee_id', 'ot_start.employee_id')
                        ->where('ot_end.type', 'overtime-end')
                        ->whereColumn('ot_end.timestamp', '>', 'ot_start.timestamp');
                })
                ->get();

            foreach ($openOT as $ot) {
                Attendance::create([
                    'employee_id'     => $ot->employee_id,
                    'type'            => 'overtime-end',
                    'timestamp'       => $now,
                    'attendance_date' => $today,
                    'late_minutes'    => 0,
                    'latitude'        => $ot->latitude ?? 0,
                    'longitude'       => $ot->longitude ?? 0,
                    'location_accuracy' => 0,
                    'notes'           => 'إنهاء دوام إضافي تلقائي - 3 فجراً',
                    'ip_address'      => 'AUTO',
                    'user_agent'      => 'AUTO-CHECKOUT-COMMAND',
                ]);
                $count++;
                $this->line("✅ إنهاء دوام إضافي تلقائي: الموظف #{$ot->employee_id}");
            }
        }

        if ($count === 0) {
            $this->info('لا يوجد موظفين بحاجة لتسجيل انصراف/إنهاء إضافي تلقائي');
            return 0;
        }

        $this->info("تم: {$count} عملية تلقائية");
        return 0;
    }
}

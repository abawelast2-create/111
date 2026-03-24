<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * إحصائيات نظرة عامة
     */
    public static function getOverviewStats(string $from, string $to): array
    {
        $totalEmployees = Employee::active()->count();

        $attendanceQuery = Attendance::whereBetween('attendance_date', [$from, $to]);

        return [
            'total_employees'   => $totalEmployees,
            'total_checkins'    => (clone $attendanceQuery)->where('type', 'in')->count(),
            'total_checkouts'   => (clone $attendanceQuery)->where('type', 'out')->count(),
            'total_late'        => (clone $attendanceQuery)->where('type', 'in')->where('late_minutes', '>', 0)->count(),
            'avg_late_minutes'  => round((clone $attendanceQuery)->where('type', 'in')->where('late_minutes', '>', 0)->avg('late_minutes') ?? 0),
            'total_overtime'    => (clone $attendanceQuery)->where('type', 'overtime-start')->count(),
            'total_leaves'      => Leave::whereBetween('start_date', [$from, $to])->count(),
            'approved_leaves'   => Leave::whereBetween('start_date', [$from, $to])->where('status', 'approved')->count(),
        ];
    }

    /**
     * منحنيات الاتجاه اليومية للتأخر والغياب
     */
    public static function getDailyTrends(string $from, string $to, ?int $branchId = null): array
    {
        $query = Attendance::where('type', 'in')
            ->whereBetween('attendance_date', [$from, $to]);

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        // اتجاه التأخر
        $lateTrend = (clone $query)->where('late_minutes', '>', 0)
            ->select('attendance_date', DB::raw('COUNT(*) as count'), DB::raw('AVG(late_minutes) as avg_late'))
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();

        // اتجاه الحضور
        $attendanceTrend = (clone $query)
            ->select('attendance_date', DB::raw('COUNT(DISTINCT employee_id) as count'))
            ->groupBy('attendance_date')
            ->orderBy('attendance_date')
            ->get();

        // حساب الغياب
        $totalActive = Employee::active()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $absentTrend = $attendanceTrend->map(function ($day) use ($totalActive) {
            return [
                'date'  => $day->attendance_date,
                'count' => max(0, $totalActive - $day->count),
            ];
        });

        return [
            'late'       => $lateTrend->map(fn ($d) => ['date' => $d->attendance_date, 'count' => $d->count, 'avg_minutes' => round($d->avg_late)])->values(),
            'attendance' => $attendanceTrend->map(fn ($d) => ['date' => $d->attendance_date, 'count' => $d->count])->values(),
            'absent'     => $absentTrend->values(),
        ];
    }

    /**
     * خريطة حرارية لتواجد الموظفين بالساعة
     */
    public static function getHeatmapData(string $from, string $to, ?int $branchId = null): array
    {
        $query = Attendance::where('type', 'in')
            ->whereBetween('attendance_date', [$from, $to]);

        if ($branchId) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        // استخراج اليوم والساعة
        if (DB::getDriverName() === 'sqlite') {
            $data = $query->select(
                DB::raw("CAST(strftime('%w', attendance_date) AS INTEGER) + 1 as day_of_week"),
                DB::raw("CAST(strftime('%H', timestamp) AS INTEGER) as hour"),
                DB::raw('COUNT(*) as count')
            );
        } else {
            $data = $query->select(
                DB::raw('DAYOFWEEK(attendance_date) as day_of_week'),
                DB::raw('HOUR(timestamp) as hour'),
                DB::raw('COUNT(*) as count')
            );
        }
        $data = $data
            ->groupBy('day_of_week', 'hour')
            ->get();

        $heatmap = [];
        foreach ($data as $item) {
            $heatmap[] = [
                'day'   => $item->day_of_week,
                'hour'  => $item->hour,
                'count' => $item->count,
            ];
        }

        return $heatmap;
    }

    /**
     * إحصائيات بالفروع
     */
    public static function getBranchStats(string $from, string $to): array
    {
        return Branch::active()->get()->map(function ($branch) use ($from, $to) {
            $empCount = $branch->employees()->active()->count();
            $checkins = Attendance::where('type', 'in')
                ->whereBetween('attendance_date', [$from, $to])
                ->whereHas('employee', fn ($q) => $q->where('branch_id', $branch->id))
                ->count();
            $lateCount = Attendance::where('type', 'in')
                ->where('late_minutes', '>', 0)
                ->whereBetween('attendance_date', [$from, $to])
                ->whereHas('employee', fn ($q) => $q->where('branch_id', $branch->id))
                ->count();

            return [
                'branch_id'   => $branch->id,
                'branch_name' => $branch->name,
                'employees'   => $empCount,
                'checkins'    => $checkins,
                'late'        => $lateCount,
                'attendance_rate' => $empCount > 0
                    ? round(($checkins / max(1, $empCount * max(1, now()->parse($from)->diffInWeekdays(now()->parse($to))))) * 100, 1)
                    : 0,
            ];
        })->values()->toArray();
    }

    /**
     * أكثر الموظفين تأخراً
     */
    public static function getTopLateEmployees(string $from, string $to, int $limit = 10): array
    {
        return Attendance::where('type', 'in')
            ->where('late_minutes', '>', 0)
            ->whereBetween('attendance_date', [$from, $to])
            ->select('employee_id', DB::raw('COUNT(*) as late_count'), DB::raw('SUM(late_minutes) as total_late'))
            ->groupBy('employee_id')
            ->orderByDesc('total_late')
            ->limit($limit)
            ->with('employee:id,name,job_title,branch_id')
            ->get()
            ->map(fn ($r) => [
                'employee'    => $r->employee?->name,
                'job_title'   => $r->employee?->job_title,
                'late_count'  => $r->late_count,
                'total_late'  => $r->total_late,
                'avg_late'    => round($r->total_late / max(1, $r->late_count)),
            ])
            ->toArray();
    }

    /**
     * التقرير المالي: حساب تكلفة العمل الإضافي والخصومات
     */
    public static function getFinancialReport(string $from, string $to, float $hourlyRate = 50): array
    {
        // العمل الإضافي
        $overtimeRecords = Attendance::whereIn('type', ['overtime-start', 'overtime-end'])
            ->whereBetween('attendance_date', [$from, $to])
            ->orderBy('employee_id')
            ->orderBy('timestamp')
            ->get()
            ->groupBy('employee_id');

        $overtimeReport = [];
        foreach ($overtimeRecords as $empId => $records) {
            $totalOvertimeMinutes = 0;
            $starts = $records->where('type', 'overtime-start');
            foreach ($starts as $start) {
                $end = $records->where('type', 'overtime-end')
                    ->where('timestamp', '>', $start->timestamp)
                    ->first();
                if ($end) {
                    $totalOvertimeMinutes += $start->timestamp->diffInMinutes($end->timestamp);
                }
            }
            if ($totalOvertimeMinutes > 0) {
                $employee = Employee::find($empId);
                $overtimeReport[] = [
                    'employee'      => $employee?->name,
                    'hours'         => round($totalOvertimeMinutes / 60, 1),
                    'cost'          => round(($totalOvertimeMinutes / 60) * $hourlyRate * 1.5, 2),
                ];
            }
        }

        // خصومات التأخير
        $lateDeductions = Attendance::where('type', 'in')
            ->where('late_minutes', '>', 0)
            ->whereBetween('attendance_date', [$from, $to])
            ->select('employee_id', DB::raw('SUM(late_minutes) as total_late'))
            ->groupBy('employee_id')
            ->get()
            ->map(function ($r) use ($hourlyRate) {
                $employee = Employee::find($r->employee_id);
                return [
                    'employee'      => $employee?->name,
                    'late_minutes'  => $r->total_late,
                    'deduction'     => round(($r->total_late / 60) * $hourlyRate, 2),
                ];
            })
            ->toArray();

        return [
            'overtime'   => $overtimeReport,
            'deductions' => $lateDeductions,
            'total_overtime_cost'  => array_sum(array_column($overtimeReport, 'cost')),
            'total_deductions'     => array_sum(array_column($lateDeductions, 'deduction')),
        ];
    }
}


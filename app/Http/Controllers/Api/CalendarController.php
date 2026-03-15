<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CalendarController extends Controller
{
    /**
     * تصدير الإجازات بتنسيق iCalendar (.ics)
     * يعمل مع Google Calendar و Outlook
     */
    public function exportLeaves(Request $request): Response
    {
        $query = Leave::with('employee:id,name')
            ->where('status', 'approved');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $leaves = $query->get();

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Sarh Attendance//Leaves//AR\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:إجازات الموظفين\r\n";
        $ical .= "X-WR-TIMEZONE:Asia/Riyadh\r\n";

        $leaveTypes = [
            'annual' => 'إجازة سنوية',
            'sick'   => 'إجازة مرضية',
            'unpaid' => 'إجازة بدون راتب',
            'other'  => 'إجازة أخرى',
        ];

        foreach ($leaves as $leave) {
            $uid = 'leave-' . $leave->id . '@sarh.io';
            $type = $leaveTypes[$leave->leave_type] ?? $leave->leave_type;
            $empName = $leave->employee?->name ?? 'موظف';

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:{$uid}\r\n";
            $ical .= "DTSTART;VALUE=DATE:" . $leave->start_date->format('Ymd') . "\r\n";
            $ical .= "DTEND;VALUE=DATE:" . $leave->end_date->addDay()->format('Ymd') . "\r\n";
            $ical .= "SUMMARY:{$type} - {$empName}\r\n";
            if ($leave->reason) {
                $ical .= "DESCRIPTION:" . str_replace(["\r", "\n"], '\\n', $leave->reason) . "\r\n";
            }
            $ical .= "STATUS:CONFIRMED\r\n";
            $ical .= "TRANSP:OPAQUE\r\n";
            $ical .= "CREATED:" . $leave->created_at->format('Ymd\THis\Z') . "\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return response($ical, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="leaves.ics"',
        ]);
    }

    /**
     * تصدير جدول الدوام بتنسيق iCalendar
     */
    public function exportSchedule(Request $request): Response
    {
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        $employee = Employee::with('branch')->findOrFail($request->employee_id);
        $branch = $employee->branch;

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Sarh Attendance//Schedule//AR\r\n";
        $ical .= "X-WR-CALNAME:جدول العمل - {$employee->name}\r\n";
        $ical .= "X-WR-TIMEZONE:Asia/Riyadh\r\n";

        if ($branch) {
            // إنشاء أحداث لمدة 30 يوم
            for ($i = 0; $i < 30; $i++) {
                $date = today()->addDays($i);
                if ($date->isFriday() || $date->isSaturday()) continue; // تخطي عطلة نهاية الأسبوع

                $uid = "schedule-{$employee->id}-{$date->format('Ymd')}@sarh.io";
                $startTime = $employee->flexible_start_time ?? $branch->work_start_time;
                $endTime = $employee->flexible_end_time ?? $branch->work_end_time;

                $ical .= "BEGIN:VEVENT\r\n";
                $ical .= "UID:{$uid}\r\n";
                $ical .= "DTSTART;TZID=Asia/Riyadh:" . $date->format('Ymd') . "T" . str_replace(':', '', $startTime) . "00\r\n";
                $ical .= "DTEND;TZID=Asia/Riyadh:" . $date->format('Ymd') . "T" . str_replace(':', '', $endTime) . "00\r\n";
                $ical .= "SUMMARY:دوام - {$branch->name}\r\n";
                $ical .= "LOCATION:{$branch->name}\r\n";
                $ical .= "STATUS:CONFIRMED\r\n";
                $ical .= "END:VEVENT\r\n";
            }
        }

        $ical .= "END:VCALENDAR\r\n";

        return response($ical, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="schedule.ics"',
        ]);
    }
}

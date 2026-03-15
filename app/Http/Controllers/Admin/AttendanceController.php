<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with('employee.branch');

        if ($request->filled('date_from')) {
            $query->where('attendance_date', '>=', $request->date_from);
        } else {
            $query->where('attendance_date', '>=', today()->toDateString());
        }
        if ($request->filled('date_to')) {
            $query->where('attendance_date', '<=', $request->date_to);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $request->branch_id));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $records   = $query->latest('timestamp')->paginate(25);
        $employees = Employee::active()->orderBy('name')->get();
        $branches  = Branch::active()->get();

        return view('admin.attendance', compact('records', 'employees', 'branches'));
    }

    public function destroy(Attendance $attendance)
    {
        AuditLog::record('delete_attendance', "حذف سجل حضور للموظف ID: {$attendance->employee_id}", $attendance->id);
        $attendance->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف السجل']);
    }

    public function lateReport(Request $request)
    {
        $from = $request->input('from', today()->startOfMonth()->toDateString());
        $to   = $request->input('to', today()->toDateString());

        $branches = Branch::active()->get();

        $query = Attendance::with('employee.branch')
            ->where('type', 'in')
            ->where('late_minutes', '>', 0)
            ->whereBetween('attendance_date', [$from, $to]);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $request->branch_id));
        }

        // Summary: group by employee
        $summaryRaw = (clone $query)->get()->groupBy('employee_id')->map(function ($records) {
            $emp = $records->first()->employee;
            $totalLate = $records->sum('late_minutes');
            $count = $records->count();
            $avgLate = $count > 0 ? round($totalLate / $count) : 0;
            $rating = $avgLate <= 5 ? 'excellent' : ($avgLate <= 15 ? 'acceptable' : 'poor');
            return (object) [
                'employee' => $emp,
                'total_late' => $totalLate,
                'late_count' => $count,
                'avg_late' => $avgLate,
                'rating' => $rating,
            ];
        })->sortByDesc('total_late')->values();

        $summary = $summaryRaw;

        // Details: paginated
        $details = $query->latest('timestamp')->paginate(25);

        return view('admin.late-report', compact('from', 'to', 'branches', 'summary', 'details'));
    }
}

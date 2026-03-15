<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $stats = Attendance::getTodayStats();
        $today = today()->toDateString();

        $recentRecords = Attendance::with('employee.branch')
            ->where('attendance_date', $today)
            ->latest('timestamp')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'employee_name' => $r->employee->name ?? '-',
                'branch_name'   => $r->employee->branch->name ?? '-',
                'type'          => $r->type,
                'timestamp'     => $r->timestamp->format('H:i:s'),
                'late_minutes'  => $r->late_minutes,
            ]);

        $checkedInIds = Attendance::where('attendance_date', $today)
            ->where('type', 'in')
            ->distinct()
            ->pluck('employee_id');

        $absentEmployees = Employee::active()
            ->whereNotIn('id', $checkedInIds)
            ->with('branch')
            ->get()
            ->map(fn ($e) => [
                'id'     => $e->id,
                'name'   => $e->name,
                'branch' => $e->branch?->name ?? '-',
            ]);

        $branches = Branch::active()->withCount('employees')->get();

        return response()->json([
            'success'    => true,
            'stats'      => $stats,
            'recent'     => $recentRecords,
            'absent'     => $absentEmployees,
            'branches'   => $branches,
            'server_time'=> now()->format('H:i:s'),
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        $query = Attendance::with('employee.branch');

        if ($request->filled('date_from')) {
            $query->where('attendance_date', '>=', $request->date_from);
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

        $records = $query->latest('timestamp')->paginate(25);

        return response()->json([
            'success' => true,
            'data'    => $records,
        ]);
    }

    public function export(Request $request)
    {
        $query = Attendance::with('employee.branch');

        if ($request->filled('date_from')) {
            $query->where('attendance_date', '>=', $request->date_from);
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

        $records = $query->latest('timestamp')->get();
        $format = $request->query('format', 'csv');

        if ($format === 'json') {
            return response()->json(ExportService::exportJson($records));
        }

        return ExportService::exportCsv($records, 'attendance_' . now()->format('Y-m-d') . '.csv');
    }

    public function health(): JsonResponse
    {
        try {
            \DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Exception $e) {
            $dbOk = false;
        }

        return response()->json([
            'status'      => $dbOk ? 'ok' : 'error',
            'database'    => $dbOk ? 'connected' : 'disconnected',
            'php_version' => PHP_VERSION,
            'laravel'     => app()->version(),
            'timestamp'   => now()->toDateTimeString(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecretReport;
use App\Models\TamperingCase;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function secretReports(Request $request)
    {
        $query = SecretReport::with('employee');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->latest()->paginate(25);

        return view('admin.secret-reports', compact('reports'));
    }

    public function updateReportStatus(Request $request, SecretReport $report)
    {
        $request->validate([
            'status'      => 'required|in:new,reviewed,in_progress,resolved,dismissed,archived',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $report->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => session('admin_id'),
        ]);

        AuditLog::record('update_report', "تحديث حالة بلاغ #{$report->id}", $report->id);

        return redirect()->route('admin.secret-reports')->with('success', 'تم تحديث حالة البلاغ');
    }

    public function tampering(Request $request)
    {
        $query = TamperingCase::with('employee');

        if ($request->filled('case_type')) {
            $query->where('case_type', $request->case_type);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $cases          = $query->latest()->paginate(25);
        $totalCases     = TamperingCase::count();
        $highSeverity   = TamperingCase::where('severity', 'high')->count();
        $mediumSeverity = TamperingCase::where('severity', 'medium')->count();
        $lowSeverity    = TamperingCase::where('severity', 'low')->count();

        return view('admin.tampering', compact('cases', 'totalCases', 'highSeverity', 'mediumSeverity', 'lowSeverity'));
    }

    public function reportCharts(Request $request)
    {
        $branches = Branch::active()->get();

        $from = $request->input('from', today()->subDays(30)->toDateString());
        $to   = $request->input('to', today()->toDateString());

        $query = Attendance::with('employee.branch')
            ->whereBetween('attendance_date', [$from, $to]);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $request->branch_id));
        }

        $records = $query->get();

        // Daily attendance chart
        $daily = $records->where('type', 'in')->groupBy('attendance_date')->map->count()->sortKeys();

        // Type distribution
        $types = $records->groupBy('type')->map->count();

        // By branch
        $byBranch = $records->where('type', 'in')->groupBy(fn ($r) => $r->employee->branch->name ?? 'بدون فرع')->map->count();

        // Late trend
        $late = $records->where('type', 'in')->where('late_minutes', '>', 0)
            ->groupBy('attendance_date')->map->count()->sortKeys();

        $chartData = [
            'daily' => ['labels' => $daily->keys(), 'data' => $daily->values()],
            'types' => ['labels' => $types->keys(), 'data' => $types->values()],
            'branches' => ['labels' => $byBranch->keys(), 'data' => $byBranch->values()],
            'late' => ['labels' => $late->keys(), 'data' => $late->values()],
        ];

        return view('admin.report-charts', compact('branches', 'chartData'));
    }
}

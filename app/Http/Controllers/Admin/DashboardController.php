<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Leave;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats      = Attendance::getTodayStats();
        $branchCount = Branch::active()->count();
        $today       = today()->toDateString();

        $recentRecords = Attendance::with('employee.branch')
            ->where('attendance_date', $today)
            ->latest('timestamp')
            ->limit(15)
            ->get();

        $checkedInIds = Attendance::where('attendance_date', $today)
            ->where('type', 'in')
            ->distinct()
            ->pluck('employee_id');

        $absentList = Employee::active()
            ->whereNotIn('id', $checkedInIds)
            ->with('branch')
            ->get();

        return view('admin.dashboard', compact('stats', 'branchCount', 'recentRecords', 'absentList'));
    }
}

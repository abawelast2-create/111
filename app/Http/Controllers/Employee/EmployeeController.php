<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Services\ScheduleService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('employee.index');
    }

    public function authByPin(Request $request)
    {
        $request->validate(['pin' => 'required|string|size:4']);

        $employee = Employee::findByPin($request->pin);
        if (!$employee) {
            return back()->withErrors(['pin' => 'رمز PIN غير صحيح']);
        }

        return redirect()->route('employee.attendance', ['token' => $employee->unique_token]);
    }

    public function attendance(Request $request)
    {
        $token = $request->query('token', '');
        if (empty($token)) {
            return redirect('/employee');
        }

        $employee = Employee::findByToken($token);
        if (!$employee) {
            return redirect('/employee')->withErrors(['token' => 'رمز غير صالح']);
        }

        $employee->load('branch');
        $schedule = ScheduleService::getBranchSchedule($employee->branch_id);

        $todayRecords = $employee->attendances()
            ->where('attendance_date', today()->toDateString())
            ->orderBy('timestamp')
            ->get();

        $lastRecord  = $todayRecords->last();
        $todayStatus = $lastRecord?->type;

        return view('employee.attendance', compact('employee', 'schedule', 'todayRecords', 'lastRecord', 'todayStatus', 'token'));
    }
}

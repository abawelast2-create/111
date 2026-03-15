<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with(['employee.branch', 'approvedByAdmin']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $request->branch_id));
        }

        $leaves        = $query->latest()->paginate(25);
        $branches      = Branch::active()->get();
        $pendingCount  = Leave::where('status', 'pending')->count();
        $approvedCount = Leave::where('status', 'approved')->count();
        $rejectedCount = Leave::where('status', 'rejected')->count();

        return view('admin.leaves', compact('leaves', 'branches', 'pendingCount', 'approvedCount', 'rejectedCount'));
    }

    public function approve(Leave $leave)
    {
        $leave->update([
            'status'      => 'approved',
            'approved_by' => session('admin_id'),
        ]);

        AuditLog::record('approve_leave', "الموافقة على إجازة للموظف: {$leave->employee->name}", $leave->id);

        return redirect()->route('admin.leaves.index')->with('success', 'تمت الموافقة على الإجازة');
    }

    public function reject(Leave $leave)
    {
        $leave->update(['status' => 'rejected']);
        AuditLog::record('reject_leave', "رفض إجازة للموظف: {$leave->employee->name}", $leave->id);

        return redirect()->route('admin.leaves.index')->with('success', 'تم رفض الإجازة');
    }
}

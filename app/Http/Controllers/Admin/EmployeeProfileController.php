<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmpDocumentGroup;
use App\Models\Branch;
use Illuminate\Http\Request;

class EmployeeProfileController extends Controller
{
    public function show(Employee $employee)
    {
        $employee->load([
            'branch',
            'documentGroups.files',
            'attendances' => fn ($q) => $q->latest('timestamp')->limit(20),
        ]);

        return view('admin.employee-profile', compact('employee'));
    }

    public function documentsExpiry(Request $request)
    {
        $query = EmpDocumentGroup::with(['employee:id,name,branch_id', 'employee.branch:id,name']);

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $request->branch_id));
        }

        $status = $request->input('status', 'all');
        if ($status === 'expired') {
            $query->whereDate('expiry_date', '<', today());
        } elseif ($status === 'soon') {
            $query->whereBetween('expiry_date', [today(), today()->addDays(30)]);
        }

        $groups = $query->orderBy('expiry_date')->paginate(25)->through(function (EmpDocumentGroup $group) {
            return [
                'id'          => $group->id,
                'employee_id' => $group->employee_id,
                'employee'    => $group->employee,
                'group_name'  => $group->group_name,
                'expiry_date' => $group->expiry_date,
                'days_left'   => $group->days_left,
                'file_count'  => $group->files()->count(),
            ];
        });

        $branches = Branch::active()->orderBy('name')->get();

        return view('admin.documents-expiry', compact('groups', 'branches', 'status'));
    }
}

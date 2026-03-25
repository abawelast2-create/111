<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('branch');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('pin', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $employees = $query->latest()->paginate(25);
        $branches  = Branch::active()->get();

        return view('admin.employees', compact('employees', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $employee = Employee::create([
            'name'         => $request->name,
            'job_title'    => $request->job_title,
            'phone'        => $request->phone,
            'branch_id'    => $request->branch_id,
            'pin'          => Employee::generatePinFromPhone($request->phone),
            'unique_token' => Employee::generateUniqueToken(),
        ]);

        AuditLog::record('add_employee', "إضافة موظف: {$employee->name}", $employee->id);

        return redirect()->route('admin.employees.index')->with('success', 'تمت إضافة الموظف بنجاح');
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'job_title'        => 'required|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'branch_id'        => 'nullable|exists:branches,id',
            'device_bind_mode' => 'nullable|in:0,1,2',
            'is_active'        => 'nullable|boolean',
        ]);

        $employee->update($request->only('name', 'job_title', 'phone', 'branch_id', 'device_bind_mode', 'is_active'));
        AuditLog::record('edit_employee', "تعديل بيانات الموظف: {$employee->name}", $employee->id);

        return redirect()->route('admin.employees.index')->with('success', 'تم تحديث بيانات الموظف');
    }

    public function destroy(Employee $employee)
    {
        AuditLog::record('delete_employee', "حذف موظف: {$employee->name}", $employee->id);
        $employee->delete(); // soft delete

        return redirect()->route('admin.employees.index')->with('success', 'تم حذف الموظف');
    }

    public function restore(int $id)
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        $employee->restore();
        AuditLog::record('restore_employee', "استعادة موظف: {$employee->name}", $employee->id);

        return redirect()->route('admin.employees.index')->with('success', 'تم استعادة الموظف');
    }

    public function regeneratePin(Employee $employee)
    {
        $newPin = Employee::generateUniquePin();
        $employee->update(['pin' => $newPin, 'pin_changed_at' => now()]);
        AuditLog::record('change_pin', "تغيير PIN للموظف: {$employee->name}", $employee->id);

        return response()->json(['success' => true, 'pin' => $newPin]);
    }

    public function resetDevice(Employee $employee)
    {
        $employee->update([
            'device_fingerprint'   => null,
            'device_registered_at' => null,
        ]);
        AuditLog::record('reset_device', "إعادة تعيين جهاز الموظف: {$employee->name}", $employee->id);

        return response()->json(['success' => true, 'message' => 'تم إعادة تعيين الجهاز']);
    }
}

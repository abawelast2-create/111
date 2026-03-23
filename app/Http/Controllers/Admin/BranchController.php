<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('employees')->get();
        return view('admin.branches', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                 => 'required|string|max:255|unique:branches',
            'latitude'             => 'required|numeric',
            'longitude'            => 'required|numeric',
            'geofence_radius'      => 'required|integer|min:10',
            'work_start_time'      => 'required',
            'work_end_time'        => 'required',
            'check_in_start_time'  => 'required',
            'check_in_end_time'    => 'required',
            'check_out_start_time' => 'required',
            'check_out_end_time'   => 'required',
        ]);

        $branch = Branch::create($request->validated());
        AuditLog::record('add_branch', "إضافة فرع: {$branch->name}", $branch->id);

        return redirect()->route('admin.branches.index')->with('success', 'تمت إضافة الفرع بنجاح');
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name'                 => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'latitude'             => 'required|numeric',
            'longitude'            => 'required|numeric',
            'geofence_radius'      => 'required|integer|min:10',
            'work_start_time'      => 'required',
            'work_end_time'        => 'required',
            'check_in_start_time'  => 'required',
            'check_in_end_time'    => 'required',
            'check_out_start_time' => 'required',
            'check_out_end_time'   => 'required',
        ]);

        $branch->update($request->validated());
        AuditLog::record('edit_branch', "تعديل فرع: {$branch->name}", $branch->id);

        return redirect()->route('admin.branches.index')->with('success', 'تم تحديث بيانات الفرع');
    }

    public function destroy(Branch $branch)
    {
        AuditLog::record('delete_branch', "حذف فرع: {$branch->name}", $branch->id);
        $branch->update(['is_active' => false]);

        return redirect()->route('admin.branches.index')->with('success', 'تم تعطيل الفرع');
    }
}

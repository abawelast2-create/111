<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\PermissionGroup;
use App\Models\AuditLog;
use App\Services\AdminPermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $admins = Admin::query()
            ->with(['permissionGroups.permissions'])
            ->orderBy('id')
            ->get();

        $groups = PermissionGroup::query()
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return view('admin.permissions', compact('admins', 'groups'));
    }

    public function assignGroups(Request $request, Admin $admin)
    {
        $validated = $request->validate([
            'permission_group_ids' => 'nullable|array',
            'permission_group_ids.*' => 'integer|exists:permission_groups,id',
            'is_super_admin' => 'nullable|boolean',
        ]);

        $groupIds = array_values(array_unique($validated['permission_group_ids'] ?? []));
        $makeSuperAdmin = (bool) ($validated['is_super_admin'] ?? false);
        $actor = Admin::find(session('admin_id'));

        if ($actor && !$actor->is_super_admin && $makeSuperAdmin !== $admin->is_super_admin) {
            return back()->with('error', 'فقط مدير النظام يمكنه تعديل صلاحية Super Admin.');
        }

        if (!$makeSuperAdmin && $admin->is_super_admin) {
            $otherSuperAdmins = Admin::where('is_super_admin', true)->where('id', '!=', $admin->id)->count();
            if ($otherSuperAdmins === 0) {
                return back()->with('error', 'لا يمكن إزالة الصلاحية المطلقة من آخر مدير نظام.');
            }
        }

        $missingDeps = AdminPermissionService::validateDependenciesForGroups($groupIds);
        if (!empty($missingDeps)) {
            $lines = collect($missingDeps)
                ->map(fn ($item) => "{$item['permission']} يحتاج {$item['required']}")
                ->unique()
                ->implode(' | ');

            return back()->with('error', 'تعذر حفظ الصلاحيات لوجود تعارض اعتماديات: ' . $lines);
        }

        $admin->permissionGroups()->sync($groupIds);
        $admin->is_super_admin = $makeSuperAdmin;
        $admin->save();

        AdminPermissionService::clearAdminCache($admin->id);

        AuditLog::record(
            'permissions_update',
            "تحديث صلاحيات المدير: {$admin->username} | مجموعات: " . implode(',', $groupIds) . " | super_admin: " . ($makeSuperAdmin ? '1' : '0'),
            $admin->id
        );

        return back()->with('success', 'تم تحديث الصلاحيات بنجاح.');
    }
}

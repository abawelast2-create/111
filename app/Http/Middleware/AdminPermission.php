<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\AdminPermissionService;
use Closure;
use Illuminate\Http\Request;

class AdminPermission
{
    public function handle(Request $request, Closure $next, string $permissionKey)
    {
        $adminId = session('admin_id');
        if (!$adminId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
            }

            return redirect()->route('admin.login');
        }

        $admin = Admin::find($adminId);
        if (!$admin) {
            session()->flush();

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'حساب غير صالح'], 401);
            }

            return redirect()->route('admin.login');
        }

        if (!AdminPermissionService::adminCan($admin, $permissionKey)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية لهذا الإجراء'], 403);
            }

            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الصفحة.');
        }

        return $next($request);
    }
}

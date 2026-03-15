<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::loadAll();
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $keys = [
            'work_latitude', 'work_longitude', 'geofence_radius',
            'work_start_time', 'work_end_time',
            'check_in_start_time', 'check_in_end_time',
            'check_out_start_time', 'check_out_end_time',
            'checkout_show_before', 'allow_overtime',
            'overtime_start_after', 'overtime_min_duration',
            'site_name', 'company_name',
            'work_start_time_2', 'work_end_time_2',
            'check_in_start_time_2', 'check_in_end_time_2',
            'check_out_start_time_2', 'check_out_end_time_2',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::setValue($key, $request->input($key));
            }
        }

        AuditLog::record('update_settings', 'تحديث إعدادات النظام');

        return redirect()->route('admin.settings.index')->with('success', 'تم حفظ الإعدادات');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $admin = Admin::find(session('admin_id'));

        if (!Hash::check($request->current_password, $admin->password_hash)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة']);
        }

        $admin->update(['password_hash' => Hash::make($request->new_password)]);
        AuditLog::record('change_password', 'تغيير كلمة المرور');

        return redirect()->route('admin.settings.index')->with('success', 'تم تغيير كلمة المرور بنجاح');
    }
}

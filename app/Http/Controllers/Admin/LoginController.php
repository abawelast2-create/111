<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showForm()
    {
        if (session('admin_id')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string',
        ]);

        $ip = $request->ip();

        // Brute force protection
        if (LoginAttempt::isLocked($ip)) {
            return back()->withErrors(['login' => 'تم تجاوز عدد المحاولات المسموحة. حاول مرة أخرى بعد 10 دقائق.']);
        }

        $admin = Admin::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password_hash)) {
            LoginAttempt::record($ip, $request->username);
            return back()->withErrors(['login' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
        }

        // التحقق من المصادقة الثنائية (2FA)
        if ($admin->two_factor_enabled) {
            session(['2fa_admin_id' => $admin->id]);
            return redirect()->route('admin.2fa.verify');
        }

        session()->regenerate();
        session([
            'admin_id'       => $admin->id,
            'admin_username' => $admin->username,
            'admin_name'     => $admin->full_name,
            'last_activity'  => time(),
        ]);

        if ($request->has('remember_me')) {
            cookie()->queue('remember_admin', '1', 43200); // 30 days
        }

        $admin->update(['last_login' => now()]);
        AuditLog::record('login', "تسجيل دخول المدير: {$admin->username}");

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        AuditLog::record('logout', 'تسجيل خروج المدير');
        session()->flush();
        cookie()->queue(cookie()->forget('remember_admin'));
        return redirect()->route('admin.login');
    }
}

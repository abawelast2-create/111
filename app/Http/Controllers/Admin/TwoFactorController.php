<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    /**
     * عرض صفحة إعدادات المصادقة الثنائية
     */
    public function index()
    {
        $admin = Admin::find(session('admin_id'));
        return view('admin.two-factor', compact('admin'));
    }

    /**
     * بدء تفعيل 2FA - توليد السر
     */
    public function enable(Request $request)
    {
        $admin = Admin::find(session('admin_id'));

        if ($admin->two_factor_enabled) {
            return back()->with('error', 'المصادقة الثنائية مفعلة بالفعل');
        }

        $setup = TwoFactorService::enable($admin);

        session(['2fa_setup' => true]);

        return view('admin.two-factor-setup', [
            'admin'         => $admin,
            'secret'        => $setup['secret'],
            'qrUri'         => $setup['qr_uri'],
            'recoveryCodes' => $setup['recovery_codes'],
        ]);
    }

    /**
     * تأكيد تفعيل 2FA
     */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $admin = Admin::find(session('admin_id'));

        if (TwoFactorService::confirm($admin, $request->code)) {
            session()->forget('2fa_setup');
            return redirect()->route('admin.settings.index')
                ->with('success', 'تم تفعيل المصادقة الثنائية بنجاح');
        }

        return back()->with('error', 'الرمز غير صحيح. حاول مرة أخرى');
    }

    /**
     * تعطيل 2FA
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $admin = Admin::find(session('admin_id'));

        if (!\Hash::check($request->password, $admin->password_hash)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة']);
        }

        TwoFactorService::disable($admin);

        return redirect()->route('admin.settings.index')
            ->with('success', 'تم تعطيل المصادقة الثنائية');
    }

    /**
     * التحقق من رمز 2FA أثناء تسجيل الدخول
     */
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $adminId = session('2fa_admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::find($adminId);
        $code = $request->code;

        // محاولة التحقق من رمز TOTP
        $secret = decrypt($admin->two_factor_secret);
        if (TwoFactorService::verifyCode($secret, $code)) {
            session()->forget('2fa_admin_id');
            session([
                'admin_id'          => $admin->id,
                'admin_name'        => $admin->full_name,
                'admin_last_active' => time(),
            ]);
            $admin->update(['last_login' => now()]);

            return redirect()->route('admin.dashboard');
        }

        // محاولة التحقق كرمز استرداد
        if (TwoFactorService::verifyRecoveryCode($admin, $code)) {
            session()->forget('2fa_admin_id');
            session([
                'admin_id'          => $admin->id,
                'admin_name'        => $admin->full_name,
                'admin_last_active' => time(),
            ]);

            return redirect()->route('admin.dashboard')
                ->with('warning', 'تم استخدام رمز استرداد. يرجى توليد رموز جديدة');
        }

        return back()->with('error', 'الرمز غير صحيح');
    }

    /**
     * عرض صفحة إدخال رمز 2FA
     */
    public function showVerifyForm()
    {
        if (!session('2fa_admin_id')) {
            return redirect()->route('admin.login');
        }
        return view('admin.two-factor-verify');
    }
}

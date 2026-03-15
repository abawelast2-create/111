<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (empty(session('admin_id'))) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
            }
            return redirect()->route('admin.login');
        }

        // Session timeout - 30 min idle
        $lastActivity = session('last_activity', time());
        $rememberMe = $request->cookie('remember_admin') === '1';
        $timeout = $rememberMe ? 2592000 : 1800; // 30 days or 30 min

        if (time() - $lastActivity > $timeout) {
            session()->flush();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'انتهت الجلسة'], 401);
            }
            return redirect()->route('admin.login')->with('error', 'انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.');
        }

        session(['last_activity' => time()]);

        // Regenerate session every 5 minutes
        $lastRegen = session('last_regeneration', 0);
        if (time() - $lastRegen > 300) {
            $request->session()->regenerate();
            session(['last_regeneration' => time()]);
        }

        return $next($request);
    }
}

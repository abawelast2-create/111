<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPinExpiry
{
    public function handle(Request $request, Closure $next)
    {
        $employee = $request->attributes->get('employee');

        if ($employee && $employee->isPinExpired()) {
            return response()->json([
                'success'     => false,
                'message'     => 'انتهت صلاحية رمز PIN. يرجى تغييره',
                'pin_expired' => true,
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function generateLink(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
        ]);

        $employee = Employee::find($request->employee_id);
        if (!$employee || !$employee->phone) {
            return response()->json(['success' => false, 'message' => 'الموظف غير موجود أو لا يوجد رقم هاتف']);
        }

        $url     = url('/employee/attendance') . '?token=' . $employee->unique_token;
        $message = urlencode("مرحباً، هذا رابط تسجيل الحضور والانصراف الخاص بك:\n{$url}\n\nيرجى استخدامه يومياً لتسجيل حضورك وانصرافك.");
        $phone   = preg_replace('/[^0-9]/', '', $employee->phone);
        $link    = "https://wa.me/{$phone}?text={$message}";

        return response()->json(['success' => true, 'link' => $link]);
    }

    public function sendAll(Request $request): JsonResponse
    {
        $employees = Employee::active()->whereNotNull('phone')->get();
        $links = [];

        foreach ($employees as $emp) {
            $url     = url('/employee/attendance') . '?token=' . $emp->unique_token;
            $message = urlencode("مرحباً {$emp->name}، هذا رابط تسجيل الحضور والانصراف الخاص بك:\n{$url}");
            $phone   = preg_replace('/[^0-9]/', '', $emp->phone);
            $links[] = [
                'name'  => $emp->name,
                'phone' => $emp->phone,
                'link'  => "https://wa.me/{$phone}?text={$message}",
            ];
        }

        AuditLog::record('send_all_links', 'تم إنشاء روابط واتساب لجميع الموظفين');

        return response()->json(['success' => true, 'links' => $links, 'count' => count($links)]);
    }

    public function regenerateTokens(Request $request): JsonResponse
    {
        $employeeId = $request->input('employee_id');

        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود']);
            }
            $employee->update(['unique_token' => Employee::generateUniqueToken()]);
            AuditLog::record('regenerate_token', "تجديد رمز الموظف: {$employee->name}", $employee->id);
            return response()->json(['success' => true, 'message' => 'تم تجديد الرمز بنجاح']);
        }

        $employees = Employee::active()->get();
        foreach ($employees as $emp) {
            $emp->update(['unique_token' => Employee::generateUniqueToken()]);
        }
        AuditLog::record('regenerate_all_tokens', 'تم تجديد جميع رموز الموظفين');

        return response()->json(['success' => true, 'message' => 'تم تجديد جميع الرموز بنجاح', 'count' => $employees->count()]);
    }
}

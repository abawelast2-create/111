<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\KnownDevice;
use App\Models\TamperingCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function authByPin(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $employee = Employee::findByPin($request->pin);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'رقم PIN غير صالح أو الموظف غير مفعّل'], 403);
        }

        return response()->json([
            'success' => true,
            'employee' => [
                'id'        => $employee->id,
                'name'      => $employee->name,
                'job_title' => $employee->job_title,
                'branch'    => $employee->branch?->name,
                'token'     => $employee->unique_token,
            ],
        ]);
    }

    public function authByDevice(Request $request): JsonResponse
    {
        $request->validate([
            'fingerprint' => 'required|string|max:64',
        ]);

        $fp = $request->fingerprint;
        $employee = Employee::where('device_fingerprint', $fp)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (!$employee) {
            return response()->json([
                'success'    => false,
                'message'    => 'الجهاز غير مسجل',
                'registered' => false,
            ]);
        }

        // تحديث عداد الاستخدام
        KnownDevice::updateOrCreate(
            ['fingerprint' => $fp, 'employee_id' => $employee->id],
            ['last_used_at' => now()]
        );
        KnownDevice::where('fingerprint', $fp)->where('employee_id', $employee->id)
            ->increment('usage_count');

        return response()->json([
            'success'  => true,
            'employee' => [
                'id'        => $employee->id,
                'name'      => $employee->name,
                'job_title' => $employee->job_title,
                'branch'    => $employee->branch?->name,
                'token'     => $employee->unique_token,
            ],
        ]);
    }

    public function verifyDevice(Request $request): JsonResponse
    {
        $request->validate([
            'token'       => 'required|string|size:64',
            'fingerprint' => 'required|string|max:64',
        ]);

        $employee = Employee::findByToken($request->token);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'رمز غير صالح'], 403);
        }

        $fp = $request->fingerprint;
        $bindMode = $employee->device_bind_mode;

        // Mode 0: Free - no binding
        if ($bindMode == 0) {
            return response()->json(['success' => true, 'message' => 'لا يوجد ربط جهاز مطلوب']);
        }

        // Already bound to this device
        if ($employee->device_fingerprint === $fp) {
            KnownDevice::updateOrCreate(
                ['fingerprint' => $fp, 'employee_id' => $employee->id],
                ['last_used_at' => now()]
            );
            return response()->json(['success' => true, 'message' => 'الجهاز مطابق']);
        }

        // New device attempting to use bound account
        if ($employee->device_fingerprint !== null) {
            // Log tampering
            TamperingCase::create([
                'employee_id'     => $employee->id,
                'case_type'       => 'device_mismatch',
                'description'     => "محاولة استخدام جهاز مختلف. البصمة المسجلة: {$employee->device_fingerprint}, البصمة الجديدة: {$fp}",
                'attendance_date' => today()->toDateString(),
                'severity'        => 'high',
                'details_json'    => ['old_fp' => $employee->device_fingerprint, 'new_fp' => $fp, 'ip' => $request->ip()],
            ]);

            if ($bindMode == 1) { // Strict: reject
                return response()->json(['success' => false, 'message' => 'هذا الجهاز غير مصرح به. يُسمح فقط بالجهاز المسجل.'], 403);
            }
            // Mode 2: Monitor - allow but log
            return response()->json(['success' => true, 'message' => 'تم تسجيل ملاحظة: جهاز مختلف']);
        }

        // First time binding
        $employee->update([
            'device_fingerprint'   => $fp,
            'device_registered_at' => now(),
        ]);

        KnownDevice::create([
            'fingerprint'  => $fp,
            'employee_id'  => $employee->id,
            'first_used_at'=> now(),
            'last_used_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم ربط الجهاز بنجاح']);
    }

    public function getEmployee(Request $request): JsonResponse
    {
        $token = $request->query('token', '');
        if (empty($token)) {
            return response()->json(['success' => false, 'message' => 'الرمز مطلوب'], 400);
        }

        $employee = Employee::findByToken($token);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'رمز غير صالح'], 403);
        }

        $lastRecord = $employee->attendances()
            ->where('attendance_date', today()->toDateString())
            ->latest('timestamp')
            ->first();

        return response()->json([
            'success'  => true,
            'employee' => [
                'id'        => $employee->id,
                'name'      => $employee->name,
                'job_title' => $employee->job_title,
                'branch'    => $employee->branch?->name,
            ],
            'last_record' => $lastRecord ? [
                'type'      => $lastRecord->type,
                'timestamp' => $lastRecord->timestamp->toDateTimeString(),
            ] : null,
        ]);
    }
}

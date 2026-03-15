<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\GeofenceService;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'token'     => 'required|string|size:64',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy'  => 'nullable|numeric',
        ]);

        $employee = Employee::findByToken($request->token);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'رمز غير صالح'], 403);
        }

        $schedule = ScheduleService::getBranchSchedule($employee->branch_id);
        if (!$schedule['allow_overtime']) {
            return response()->json(['success' => false, 'message' => 'الدوام الإضافي غير مسموح لهذا الفرع']);
        }

        // التحقق من وجود تسجيل انصراف اليوم
        $hasCheckout = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', today()->toDateString())
            ->where('type', 'out')
            ->exists();

        if (!$hasCheckout) {
            return response()->json(['success' => false, 'message' => 'يجب تسجيل الانصراف أولاً قبل بدء الدوام الإضافي']);
        }

        $geoCheck = GeofenceService::isWithinGeofence($request->latitude, $request->longitude, $employee->branch_id);
        if (!$geoCheck['allowed']) {
            return response()->json(['success' => false, 'message' => $geoCheck['message'], 'distance' => $geoCheck['distance']]);
        }

        $result = AttendanceService::record($employee->id, 'overtime-start', $request->latitude, $request->longitude, $request->accuracy ?? 0);

        return response()->json(array_merge($result, [
            'employee_name' => $employee->name,
            'timestamp'     => now()->toDateTimeString(),
        ]));
    }

    public function end(Request $request): JsonResponse
    {
        $request->validate([
            'token'     => 'required|string|size:64',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy'  => 'nullable|numeric',
        ]);

        $employee = Employee::findByToken($request->token);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'رمز غير صالح'], 403);
        }

        // التحقق من وجود بدء دوام إضافي
        $otStart = Attendance::where('employee_id', $employee->id)
            ->where('attendance_date', today()->toDateString())
            ->where('type', 'overtime-start')
            ->latest('timestamp')
            ->first();

        if (!$otStart) {
            return response()->json(['success' => false, 'message' => 'لم يتم بدء دوام إضافي اليوم']);
        }

        // التحقق من الحد الأدنى للمدة
        $schedule = ScheduleService::getBranchSchedule($employee->branch_id);
        $minDuration = $schedule['overtime_min_duration'];
        $durationMinutes = now()->diffInMinutes($otStart->timestamp);

        if ($durationMinutes < $minDuration) {
            return response()->json([
                'success' => false,
                'message' => "الحد الأدنى للدوام الإضافي {$minDuration} دقيقة. المدة الحالية: {$durationMinutes} دقيقة",
            ]);
        }

        $result = AttendanceService::record($employee->id, 'overtime-end', $request->latitude, $request->longitude, $request->accuracy ?? 0);

        return response()->json(array_merge($result, [
            'employee_name' => $employee->name,
            'timestamp'     => now()->toDateTimeString(),
            'duration'      => $durationMinutes,
        ]));
    }
}

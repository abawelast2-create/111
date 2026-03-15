<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\GeofenceService;
use App\Services\GpsValidationService;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'token'     => 'required|string|size:64',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy'  => 'nullable|numeric',
            'wifi_networks' => 'nullable|array',
            'mock_location' => 'nullable|boolean',
        ]);

        $employee = Employee::findByToken($request->token);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'رمز غير صالح أو الموظف غير مفعّل'], 403);
        }

        // التحقق من نافذة وقت الدوام
        $schedule = ScheduleService::getBranchSchedule($employee->branch_id);
        $nowTime  = now()->format('H:i');
        if (!AttendanceService::isWithinTimeWindow($nowTime, $schedule['check_in_start_time'], $schedule['check_in_end_time'])) {
            return response()->json([
                'success' => false,
                'message' => "وقت تسجيل الدخول المسموح به: {$schedule['check_in_start_time']} - {$schedule['check_in_end_time']}. الوقت الحالي: {$nowTime}",
            ]);
        }

        // التحقق من النطاق الجغرافي
        // التحقق من صحة الموقع الجغرافي (كشف التزوير)
        $gpsValidation = GpsValidationService::validate(
            $employee,
            $request->latitude,
            $request->longitude,
            $request->accuracy,
            $request->wifi_networks,
            $request->ip()
        );

        if (!$gpsValidation['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $gpsValidation['message'],
                'score'   => $gpsValidation['score'],
                'flags'   => $gpsValidation['flags'],
            ], 403);
        }

        $geoCheck = GeofenceService::isWithinGeofence($request->latitude, $request->longitude, $employee->branch_id);
        if (!$geoCheck['allowed']) {
            return response()->json([
                'success'  => false,
                'message'  => $geoCheck['message'],
                'distance' => $geoCheck['distance'],
            ]);
        }

        $result = AttendanceService::record(
            $employee->id, 'in', $request->latitude, $request->longitude,
            $request->accuracy ?? 0, $request->boolean('mock_location'),
            $gpsValidation['score'], $request->wifi_networks
        );

        return response()->json(array_merge($result, [
            'employee_name' => $employee->name,
            'timestamp'     => now()->toDateTimeString(),
            'distance'      => $geoCheck['distance'] ?? 0,
        ]));
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;

// =================== المصادقة ===================
Route::post('auth-pin', [Api\AuthController::class, 'authByPin'])->middleware('throttle:10,1');
Route::post('auth-device', [Api\AuthController::class, 'authByDevice'])->middleware('throttle:20,1');
Route::post('verify-device', [Api\AuthController::class, 'verifyDevice'])->middleware('throttle:30,1');
Route::get('get-employee', [Api\AuthController::class, 'getEmployee']);

// =================== تسجيل الحضور والانصراف ===================
Route::post('check-in', Api\CheckInController::class)->middleware('throttle:30,1');
Route::post('check-out', Api\CheckOutController::class)->middleware('throttle:30,1');

// =================== الدوام الإضافي ===================
Route::post('overtime', [Api\OvertimeController::class, 'start'])->middleware('throttle:20,1');
Route::post('ot', [Api\OvertimeController::class, 'start'])->middleware('throttle:20,1');
Route::post('overtime-end', [Api\OvertimeController::class, 'end'])->middleware('throttle:20,1');

// =================== البلاغات والإجازات ===================
Route::post('submit-report', [Api\ReportController::class, 'submitReport'])->middleware('throttle:10,60');
Route::post('leave-add', [Api\ReportController::class, 'addLeave'])->middleware('admin.auth');

// =================== مسارات تحتاج صلاحية المدير ===================
Route::middleware('admin.auth')->group(function () {
    Route::get('realtime-dashboard', [Api\RealtimeController::class, 'dashboard']);
    Route::get('realtime-attendance', [Api\RealtimeController::class, 'attendance']);
    Route::get('export', [Api\RealtimeController::class, 'export']);
    Route::post('send-all-links', [Api\WhatsAppController::class, 'sendAll']);
    Route::post('regenerate-tokens', [Api\WhatsAppController::class, 'regenerateTokens']);
    Route::post('whatsapp', [Api\WhatsAppController::class, 'generateLink']);
});

// =================== API عام (Sanctum) ===================
Route::post('tokens/create', [Api\PublicApiController::class, 'createToken'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('tokens/revoke', [Api\PublicApiController::class, 'revokeToken']);

    // الحضور
    Route::get('v1/attendance', [Api\PublicApiController::class, 'attendanceIndex']);

    // الموظفون
    Route::get('v1/employees', [Api\PublicApiController::class, 'employeeIndex']);
    Route::get('v1/employees/{employee}', [Api\PublicApiController::class, 'employeeShow']);

    // الفروع
    Route::get('v1/branches', [Api\PublicApiController::class, 'branchIndex']);

    // الإجازات
    Route::get('v1/leaves', [Api\PublicApiController::class, 'leaveIndex']);
    Route::post('v1/leaves', [Api\PublicApiController::class, 'leaveStore']);
    Route::post('v1/leaves/{leave}/approve', [Api\PublicApiController::class, 'leaveApprove']);
    Route::post('v1/leaves/{leave}/reject', [Api\PublicApiController::class, 'leaveReject']);
});

// =================== التقويم ===================
Route::get('calendar/leaves', [Api\CalendarController::class, 'exportLeaves'])->middleware('admin.auth');
Route::get('calendar/schedule', [Api\CalendarController::class, 'exportSchedule'])->middleware('admin.auth');

// =================== الإشعارات ===================
Route::middleware('admin.auth')->group(function () {
    Route::get('notifications', function () {
        return response()->json(\App\Services\NotificationService::getRecent(
            \App\Models\Admin::class, session('admin_id'), 20
        ));
    });
});

// =================== النظام ===================
Route::get('health', [Api\RealtimeController::class, 'health']);

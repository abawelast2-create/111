<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Employee;

// =================== الصفحة الرئيسية ===================
Route::get('/', fn () => redirect('/employee'));

// =================== مسارات المدير ===================
Route::prefix('admin')->group(function () {
    Route::get('login', [Admin\LoginController::class, 'showForm'])->name('admin.login');
    Route::post('login', [Admin\LoginController::class, 'login'])->name('admin.login.submit');
    Route::post('logout', [Admin\LoginController::class, 'logout'])->name('admin.logout');

    // المصادقة الثنائية
    Route::get('2fa/verify', [Admin\TwoFactorController::class, 'showVerifyForm'])->name('admin.2fa.verify');
    Route::post('2fa/verify', [Admin\TwoFactorController::class, 'verify'])->name('admin.2fa.verify.submit');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

        // الموظفين
        Route::get('employees', [Admin\EmployeeController::class, 'index'])->name('admin.employees.index');
        Route::post('employees', [Admin\EmployeeController::class, 'store'])->name('admin.employees.store');
        Route::put('employees/{employee}', [Admin\EmployeeController::class, 'update'])->name('admin.employees.update');
        Route::delete('employees/{employee}', [Admin\EmployeeController::class, 'destroy'])->name('admin.employees.destroy');
        Route::post('employees/{id}/restore', [Admin\EmployeeController::class, 'restore'])->name('admin.employees.restore');
        Route::post('employees/{employee}/regenerate-pin', [Admin\EmployeeController::class, 'regeneratePin'])->name('admin.employees.regenerate-pin');
        Route::post('employees/{employee}/reset-device', [Admin\EmployeeController::class, 'resetDevice'])->name('admin.employees.reset-device');

        // الفروع
        Route::get('branches', [Admin\BranchController::class, 'index'])->name('admin.branches.index');
        Route::post('branches', [Admin\BranchController::class, 'store'])->name('admin.branches.store');
        Route::put('branches/{branch}', [Admin\BranchController::class, 'update'])->name('admin.branches.update');
        Route::delete('branches/{branch}', [Admin\BranchController::class, 'destroy'])->name('admin.branches.destroy');

        // الحضور والانصراف
        Route::get('attendance', [Admin\AttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::delete('attendance/{attendance}', [Admin\AttendanceController::class, 'destroy'])->name('admin.attendance.destroy');
        Route::get('late-report', [Admin\AttendanceController::class, 'lateReport'])->name('admin.late-report');

        // الإجازات
        Route::get('leaves', [Admin\LeaveController::class, 'index'])->name('admin.leaves.index');
        Route::post('leaves/{leave}/approve', [Admin\LeaveController::class, 'approve'])->name('admin.leaves.approve');
        Route::post('leaves/{leave}/reject', [Admin\LeaveController::class, 'reject'])->name('admin.leaves.reject');

        // الإعدادات
        Route::get('settings', [Admin\SettingsController::class, 'index'])->name('admin.settings.index');
        Route::post('settings', [Admin\SettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('settings/change-password', [Admin\SettingsController::class, 'changePassword'])->name('admin.settings.change-password');

        // البلاغات والتلاعب
        Route::get('secret-reports', [Admin\ReportController::class, 'secretReports'])->name('admin.secret-reports');
        Route::put('secret-reports/{report}', [Admin\ReportController::class, 'updateReportStatus'])->name('admin.secret-reports.update');
        Route::get('tampering', [Admin\ReportController::class, 'tampering'])->name('admin.tampering');
        Route::get('report-charts', [Admin\ReportController::class, 'reportCharts'])->name('admin.report-charts');

        // التحليلات المتقدمة
        Route::get('analytics', [Admin\AnalyticsController::class, 'index'])->name('admin.analytics');
        Route::get('analytics/data', [Admin\AnalyticsController::class, 'data'])->name('admin.analytics.data');

        // الإشعارات
        Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('admin.notifications');
        Route::post('notifications/mark-all-read', [Admin\NotificationController::class, 'markAllRead'])->name('admin.notifications.mark-all-read');
        Route::post('notifications/{notification}/mark-read', [Admin\NotificationController::class, 'markRead'])->name('admin.notifications.mark-read');
        Route::get('notifications/unread-count', [Admin\NotificationController::class, 'unreadCount'])->name('admin.notifications.unread-count');

        // النسخ الاحتياطي
        Route::get('backups', [Admin\BackupController::class, 'index'])->name('admin.backups');
        Route::post('backups', [Admin\BackupController::class, 'create'])->name('admin.backups.create');
        Route::get('backups/{backup}/download', [Admin\BackupController::class, 'download'])->name('admin.backups.download');
        Route::delete('backups/{backup}', [Admin\BackupController::class, 'destroy'])->name('admin.backups.destroy');

        // Webhooks
        Route::get('webhooks', [Admin\WebhookController::class, 'index'])->name('admin.webhooks');
        Route::post('webhooks', [Admin\WebhookController::class, 'store'])->name('admin.webhooks.store');
        Route::put('webhooks/{webhook}', [Admin\WebhookController::class, 'update'])->name('admin.webhooks.update');
        Route::delete('webhooks/{webhook}', [Admin\WebhookController::class, 'destroy'])->name('admin.webhooks.destroy');
        Route::post('webhooks/{webhook}/regenerate-secret', [Admin\WebhookController::class, 'regenerateSecret'])->name('admin.webhooks.regenerate-secret');
        Route::get('webhooks/{webhook}/logs', [Admin\WebhookController::class, 'logs'])->name('admin.webhooks.logs');

        // المصادقة الثنائية (إعدادات)
        Route::get('2fa', [Admin\TwoFactorController::class, 'index'])->name('admin.2fa.index');
        Route::post('2fa/enable', [Admin\TwoFactorController::class, 'enable'])->name('admin.2fa.enable');
        Route::post('2fa/confirm', [Admin\TwoFactorController::class, 'confirm'])->name('admin.2fa.confirm');
        Route::post('2fa/disable', [Admin\TwoFactorController::class, 'disable'])->name('admin.2fa.disable');
    });
});

// =================== مسارات الموظف ===================
Route::prefix('employee')->group(function () {
    Route::get('/', [Employee\EmployeeController::class, 'index'])->name('employee.index');
    Route::post('/', [Employee\EmployeeController::class, 'authByPin'])->name('employee.auth');
    Route::get('attendance', [Employee\EmployeeController::class, 'attendance'])->name('employee.attendance');
});

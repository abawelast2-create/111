<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Employee;

// =================== الصفحة الرئيسية ===================
Route::get('/', fn () => view('welcome'));

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
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])
            ->middleware('admin.permission:dashboard.view')
            ->name('admin.dashboard');

        // الموظفين
        Route::get('employees', [Admin\EmployeeController::class, 'index'])
            ->middleware('admin.permission:employees.view')
            ->name('admin.employees.index');
        Route::post('employees', [Admin\EmployeeController::class, 'store'])
            ->middleware('admin.permission:employees.create')
            ->name('admin.employees.store');
        Route::put('employees/{employee}', [Admin\EmployeeController::class, 'update'])
            ->middleware('admin.permission:employees.update')
            ->name('admin.employees.update');
        Route::delete('employees/{employee}', [Admin\EmployeeController::class, 'destroy'])
            ->middleware('admin.permission:employees.delete')
            ->name('admin.employees.destroy');
        Route::post('employees/{id}/restore', [Admin\EmployeeController::class, 'restore'])
            ->middleware('admin.permission:employees.update')
            ->name('admin.employees.restore');
        Route::post('employees/{employee}/regenerate-pin', [Admin\EmployeeController::class, 'regeneratePin'])
            ->middleware('admin.permission:employees.pin.regenerate')
            ->name('admin.employees.regenerate-pin');
        Route::post('employees/{employee}/reset-device', [Admin\EmployeeController::class, 'resetDevice'])
            ->middleware('admin.permission:employees.device.reset')
            ->name('admin.employees.reset-device');
        Route::get('employees/{employee}/profile', [Admin\EmployeeProfileController::class, 'show'])
            ->middleware('admin.permission:employees.profile')
            ->name('admin.employees.profile');
        Route::get('documents-expiry', [Admin\EmployeeProfileController::class, 'documentsExpiry'])
            ->middleware('admin.permission:employees.documents_expiry')
            ->name('admin.documents-expiry');

        // الفروع
        Route::get('branches', [Admin\BranchController::class, 'index'])
            ->middleware('admin.permission:branches.view')
            ->name('admin.branches.index');
        Route::post('branches', [Admin\BranchController::class, 'store'])
            ->middleware('admin.permission:branches.manage')
            ->name('admin.branches.store');
        Route::put('branches/{branch}', [Admin\BranchController::class, 'update'])
            ->middleware('admin.permission:branches.manage')
            ->name('admin.branches.update');
        Route::delete('branches/{branch}', [Admin\BranchController::class, 'destroy'])
            ->middleware('admin.permission:branches.manage')
            ->name('admin.branches.destroy');

        // الحضور والانصراف
        Route::get('attendance', [Admin\AttendanceController::class, 'index'])
            ->middleware('admin.permission:attendance.view')
            ->name('admin.attendance.index');
        Route::delete('attendance/{attendance}', [Admin\AttendanceController::class, 'destroy'])
            ->middleware('admin.permission:attendance.delete')
            ->name('admin.attendance.destroy');
        Route::get('late-report', [Admin\AttendanceController::class, 'lateReport'])
            ->middleware('admin.permission:attendance.late_report')
            ->name('admin.late-report');
        Route::get('attendance/today-stats', [Admin\AttendanceController::class, 'todayStats'])
            ->middleware('admin.permission:attendance.view')
            ->name('admin.attendance.todayStats');

        // الإجازات
        Route::get('leaves', [Admin\LeaveController::class, 'index'])
            ->middleware('admin.permission:leaves.view')
            ->name('admin.leaves.index');
        Route::post('leaves/{leave}/approve', [Admin\LeaveController::class, 'approve'])
            ->middleware('admin.permission:leaves.approve')
            ->name('admin.leaves.approve');
        Route::post('leaves/{leave}/reject', [Admin\LeaveController::class, 'reject'])
            ->middleware('admin.permission:leaves.reject')
            ->name('admin.leaves.reject');

        // الإعدادات
        Route::get('settings', [Admin\SettingsController::class, 'index'])
            ->middleware('admin.permission:settings.view')
            ->name('admin.settings.index');
        Route::post('settings', [Admin\SettingsController::class, 'update'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.settings.update');
        Route::post('settings/change-password', [Admin\SettingsController::class, 'changePassword'])
            ->middleware('admin.permission:settings.change_password')
            ->name('admin.settings.change-password');

        // البلاغات والتلاعب
        Route::get('secret-reports', [Admin\ReportController::class, 'secretReports'])
            ->middleware('admin.permission:secret_reports.view')
            ->name('admin.secret-reports');
        Route::put('secret-reports/{report}', [Admin\ReportController::class, 'updateReportStatus'])
            ->middleware('admin.permission:secret_reports.update')
            ->name('admin.secret-reports.update');
        Route::get('tampering', [Admin\ReportController::class, 'tampering'])
            ->middleware('admin.permission:tampering.view')
            ->name('admin.tampering');
        Route::get('report-charts', [Admin\ReportController::class, 'reportCharts'])
            ->middleware('admin.permission:attendance.charts')
            ->name('admin.report-charts');

        // التحليلات المتقدمة
        Route::get('analytics', [Admin\AnalyticsController::class, 'index'])
            ->middleware('admin.permission:analytics.view')
            ->name('admin.analytics.index');
        Route::get('analytics/data', [Admin\AnalyticsController::class, 'data'])
            ->middleware('admin.permission:analytics.view')
            ->name('admin.analytics.data');

        // الإشعارات
        Route::get('notifications', [Admin\NotificationController::class, 'index'])
            ->middleware('admin.permission:notifications.view')
            ->name('admin.notifications.index');
        Route::post('notifications/mark-all-read', [Admin\NotificationController::class, 'markAllRead'])
            ->middleware('admin.permission:notifications.manage')
            ->name('admin.notifications.mark-all-read');
        Route::post('notifications/{notification}/mark-read', [Admin\NotificationController::class, 'markRead'])
            ->middleware('admin.permission:notifications.manage')
            ->name('admin.notifications.mark-read');
        Route::get('notifications/unread-count', [Admin\NotificationController::class, 'unreadCount'])
            ->middleware('admin.permission:notifications.view')
            ->name('admin.notifications.unread-count');

        // النسخ الاحتياطي
        Route::get('backups', [Admin\BackupController::class, 'index'])
            ->middleware('admin.permission:backups.view')
            ->name('admin.backups.index');
        Route::post('backups', [Admin\BackupController::class, 'create'])
            ->middleware('admin.permission:backups.create')
            ->name('admin.backups.create');
        Route::get('backups/{backup}/download', [Admin\BackupController::class, 'download'])
            ->middleware('admin.permission:backups.download')
            ->name('admin.backups.download');
        Route::delete('backups/{backup}', [Admin\BackupController::class, 'destroy'])
            ->middleware('admin.permission:backups.delete')
            ->name('admin.backups.destroy');

        // Webhooks
        Route::get('webhooks', [Admin\WebhookController::class, 'index'])
            ->middleware('admin.permission:webhooks.view')
            ->name('admin.webhooks.index');
        Route::post('webhooks', [Admin\WebhookController::class, 'store'])
            ->middleware('admin.permission:webhooks.manage')
            ->name('admin.webhooks.store');
        Route::put('webhooks/{webhook}', [Admin\WebhookController::class, 'update'])
            ->middleware('admin.permission:webhooks.manage')
            ->name('admin.webhooks.update');
        Route::delete('webhooks/{webhook}', [Admin\WebhookController::class, 'destroy'])
            ->middleware('admin.permission:webhooks.manage')
            ->name('admin.webhooks.destroy');
        Route::post('webhooks/{webhook}/regenerate-secret', [Admin\WebhookController::class, 'regenerateSecret'])
            ->middleware('admin.permission:webhooks.regenerate_secret')
            ->name('admin.webhooks.regenerate-secret');
        Route::get('webhooks/{webhook}/logs', [Admin\WebhookController::class, 'logs'])
            ->middleware('admin.permission:webhooks.logs')
            ->name('admin.webhooks.logs');

        // المصادقة الثنائية (إعدادات)
        Route::get('2fa', [Admin\TwoFactorController::class, 'index'])
            ->middleware('admin.permission:twofactor.manage')
            ->name('admin.2fa.index');
        Route::post('2fa/enable', [Admin\TwoFactorController::class, 'enable'])
            ->middleware('admin.permission:twofactor.manage')
            ->name('admin.2fa.enable');
        Route::post('2fa/confirm', [Admin\TwoFactorController::class, 'confirm'])
            ->middleware('admin.permission:twofactor.manage')
            ->name('admin.2fa.confirm');
        Route::post('2fa/disable', [Admin\TwoFactorController::class, 'disable'])
            ->middleware('admin.permission:twofactor.manage')
            ->name('admin.2fa.disable');

        // إدارة الصلاحيات
        Route::get('permissions', [Admin\PermissionController::class, 'index'])
            ->middleware('admin.permission:permissions.manage')
            ->name('admin.permissions.index');
        Route::post('permissions/{admin}/assign', [Admin\PermissionController::class, 'assignGroups'])
            ->middleware('admin.permission:permissions.manage')
            ->name('admin.permissions.assign');

        // تقارير البريد الإلكتروني
        Route::get('report-schedules', [Admin\ReportMailController::class, 'index'])
            ->middleware('admin.permission:settings.view')
            ->name('admin.report-schedules.index');
        Route::post('report-schedules', [Admin\ReportMailController::class, 'store'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.report-schedules.store');
        Route::put('report-schedules/{schedule}', [Admin\ReportMailController::class, 'update'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.report-schedules.update');
        Route::delete('report-schedules/{schedule}', [Admin\ReportMailController::class, 'destroy'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.report-schedules.destroy');
        Route::post('report-schedules/{schedule}/toggle', [Admin\ReportMailController::class, 'toggle'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.report-schedules.toggle');
        Route::post('report-schedules/send-now', [Admin\ReportMailController::class, 'sendNow'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.report-schedules.send-now');

        // مولّد البيانات
        Route::get('data-generator', [Admin\DataGeneratorController::class, 'index'])
            ->middleware('admin.permission:settings.view')
            ->name('admin.data-generator.index');
        Route::get('data-generator/preview', [Admin\DataGeneratorController::class, 'preview'])
            ->middleware('admin.permission:settings.view')
            ->name('admin.data-generator.preview');
        Route::post('data-generator/generate', [Admin\DataGeneratorController::class, 'generate'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.data-generator.generate');
        Route::post('data-generator/cleanup', [Admin\DataGeneratorController::class, 'cleanup'])
            ->middleware('admin.permission:settings.update')
            ->name('admin.data-generator.cleanup');
    });
});

// =================== مسارات الموظف ===================
Route::prefix('employee')->group(function () {
    Route::get('/', [Employee\EmployeeController::class, 'index'])->name('employee.index');
    Route::post('/', [Employee\EmployeeController::class, 'authByPin'])->name('employee.auth');
    Route::get('attendance', [Employee\EmployeeController::class, 'attendance'])->name('employee.attendance');
});

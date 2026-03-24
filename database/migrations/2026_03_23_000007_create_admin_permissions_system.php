<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('full_name');
        });

        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 120)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->string('permission_key', 150)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->json('depends_on')->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('admin_permission_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('permission_group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['admin_id', 'permission_group_id']);
        });

        $groups = [
            ['group_key' => 'dashboard', 'name' => 'لوحة التحكم', 'description' => 'عرض مؤشرات النظام الأساسية'],
            ['group_key' => 'employees', 'name' => 'إدارة الموظفين', 'description' => 'إدارة بيانات الموظفين وأجهزتهم'],
            ['group_key' => 'branches', 'name' => 'إدارة الفروع', 'description' => 'إدارة الفروع وإعدادات كل فرع'],
            ['group_key' => 'attendance', 'name' => 'تقارير الحضور', 'description' => 'عرض وتعديل تقارير الحضور'],
            ['group_key' => 'leaves', 'name' => 'الإجازات', 'description' => 'عرض وقرارات طلبات الإجازة'],
            ['group_key' => 'reports_security', 'name' => 'البلاغات والأمن', 'description' => 'التقارير السرية وحالات التلاعب'],
            ['group_key' => 'analytics', 'name' => 'التحليلات', 'description' => 'التحليلات والتقارير البيانية'],
            ['group_key' => 'notifications', 'name' => 'الإشعارات', 'description' => 'عرض وإدارة الإشعارات'],
            ['group_key' => 'backup', 'name' => 'النسخ الاحتياطي', 'description' => 'إنشاء وحذف وتنزيل النسخ الاحتياطية'],
            ['group_key' => 'integrations', 'name' => 'التكاملات', 'description' => 'Webhooks وإدارة مفاتيحها'],
            ['group_key' => 'system_settings', 'name' => 'إعدادات النظام', 'description' => 'الإعدادات العامة وكلمة المرور والمصادقة الثنائية'],
            ['group_key' => 'permission_admin', 'name' => 'إدارة الصلاحيات', 'description' => 'تعيين مجموعات الصلاحيات للمستخدمين الإداريين'],
        ];

        foreach ($groups as $group) {
            DB::table('permission_groups')->insert(array_merge($group, [
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $groupIds = DB::table('permission_groups')->pluck('id', 'group_key');

        $permissions = [
            ['dashboard', 'dashboard.view', 'استعراض لوحة التحكم', null, []],

            ['employees', 'employees.view', 'استعراض الموظفين', null, []],
            ['employees', 'employees.create', 'إضافة موظف', null, ['employees.view']],
            ['employees', 'employees.update', 'تعديل موظف', null, ['employees.view']],
            ['employees', 'employees.delete', 'حذف موظف', null, ['employees.view']],
            ['employees', 'employees.profile', 'استعراض ملف الموظف', null, ['employees.view']],
            ['employees', 'employees.documents_expiry', 'استعراض انتهاء الوثائق', null, ['employees.view']],
            ['employees', 'employees.pin.regenerate', 'إعادة توليد PIN', null, ['employees.view']],
            ['employees', 'employees.device.reset', 'إعادة تعيين جهاز الموظف', null, ['employees.view']],

            ['branches', 'branches.view', 'استعراض الفروع', null, []],
            ['branches', 'branches.manage', 'إدارة الفروع (إضافة/تعديل/حذف)', null, ['branches.view']],

            ['attendance', 'attendance.view', 'استعراض تقارير الحضور', null, []],
            ['attendance', 'attendance.delete', 'حذف سجل حضور', 'لا يمكن حذف سجل دون استعراض التقرير.', ['attendance.view']],
            ['attendance', 'attendance.late_report', 'استعراض تقرير التأخير', null, ['attendance.view']],
            ['attendance', 'attendance.charts', 'استعراض الرسوم البيانية للحضور', null, ['attendance.view']],

            ['leaves', 'leaves.view', 'استعراض الإجازات', null, []],
            ['leaves', 'leaves.approve', 'اعتماد طلب الإجازة', null, ['leaves.view']],
            ['leaves', 'leaves.reject', 'رفض طلب الإجازة', null, ['leaves.view']],

            ['reports_security', 'tampering.view', 'استعراض حالات التلاعب', null, []],
            ['reports_security', 'secret_reports.view', 'استعراض التقارير السرية', null, []],
            ['reports_security', 'secret_reports.update', 'تحديث حالة التقارير السرية', null, ['secret_reports.view']],

            ['analytics', 'analytics.view', 'استعراض التحليلات المتقدمة', null, []],

            ['notifications', 'notifications.view', 'استعراض الإشعارات', null, []],
            ['notifications', 'notifications.manage', 'إدارة الإشعارات (تعيين كمقروء)', null, ['notifications.view']],

            ['backup', 'backups.view', 'استعراض النسخ الاحتياطي', null, []],
            ['backup', 'backups.create', 'إنشاء نسخة احتياطية', null, ['backups.view']],
            ['backup', 'backups.download', 'تنزيل نسخة احتياطية', null, ['backups.view']],
            ['backup', 'backups.delete', 'حذف نسخة احتياطية', null, ['backups.view']],

            ['integrations', 'webhooks.view', 'استعراض Webhooks', null, []],
            ['integrations', 'webhooks.manage', 'إدارة Webhooks', null, ['webhooks.view']],
            ['integrations', 'webhooks.regenerate_secret', 'تجديد Webhook Secret', null, ['webhooks.view']],
            ['integrations', 'webhooks.logs', 'استعراض سجلات Webhooks', null, ['webhooks.view']],

            ['system_settings', 'settings.view', 'استعراض إعدادات النظام', null, []],
            ['system_settings', 'settings.update', 'تعديل إعدادات النظام', null, ['settings.view']],
            ['system_settings', 'settings.change_password', 'تغيير كلمة المرور', null, ['settings.view']],
            ['system_settings', 'twofactor.manage', 'إدارة المصادقة الثنائية', null, ['settings.view']],

            ['permission_admin', 'permissions.manage', 'إدارة مجموعات الصلاحيات', null, []],
        ];

        foreach ($permissions as [$groupKey, $permissionKey, $name, $description, $dependsOn]) {
            DB::table('permissions')->insert([
                'permission_group_id' => $groupIds[$groupKey],
                'permission_key' => $permissionKey,
                'name' => $name,
                'description' => $description,
                'depends_on' => empty($dependsOn) ? null : json_encode($dependsOn),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $firstAdmin = DB::table('admins')->orderBy('id')->value('id');
        if ($firstAdmin) {
            DB::table('admins')->where('id', $firstAdmin)->update(['is_super_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permission_group');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_groups');

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};

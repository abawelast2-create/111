<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // تدوير PIN الإجباري
            $table->timestamp('pin_expires_at')->nullable()->after('pin_changed_at');
            $table->integer('pin_rotation_days')->default(90)->after('pin_expires_at');

            // ساعات العمل المرنة
            $table->time('flexible_start_time')->nullable()->after('is_active');
            $table->time('flexible_end_time')->nullable()->after('flexible_start_time');
            $table->integer('flexible_window_minutes')->default(0)->after('flexible_end_time');

            // دورة حياة الموظف
            $table->date('hire_date')->nullable()->after('flexible_window_minutes');
            $table->date('termination_date')->nullable()->after('hire_date');
            $table->string('employment_status', 20)->default('active')->after('termination_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'pin_expires_at', 'pin_rotation_days',
                'flexible_start_time', 'flexible_end_time', 'flexible_window_minutes',
                'hire_date', 'termination_date', 'employment_status',
            ]);
        });
    }
};

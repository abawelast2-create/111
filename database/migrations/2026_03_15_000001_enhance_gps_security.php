<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة حقول التحقق من GPS إلى جدول الحضور
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('mock_location_detected')->default(false)->after('location_accuracy');
            $table->decimal('validation_score', 5, 2)->nullable()->after('mock_location_detected');
            $table->json('wifi_networks')->nullable()->after('validation_score');
            $table->boolean('ip_location_match')->nullable()->after('wifi_networks');
        });

        // جدول سجل المواقع لتتبع أنماط التنقل
        Schema::create('location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->string('suspicion_reason', 255)->nullable();
            $table->timestamp('recorded_at');
            $table->index(['employee_id', 'recorded_at']);
        });

        // إضافة نطاق ثقة لكل موظف
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('trust_radius')->nullable()->after('security_level');
            $table->decimal('avg_latitude', 10, 8)->nullable()->after('trust_radius');
            $table->decimal('avg_longitude', 11, 8)->nullable()->after('avg_latitude');
        });

        // إضافة نطاقات IP للفروع
        Schema::table('branches', function (Blueprint $table) {
            $table->string('allowed_ip_ranges', 500)->nullable()->after('geofence_radius');
            $table->string('city', 100)->nullable()->after('allowed_ip_ranges');
            $table->string('wifi_ssids', 500)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['mock_location_detected', 'validation_score', 'wifi_networks', 'ip_location_match']);
        });

        Schema::dropIfExists('location_logs');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['trust_radius', 'avg_latitude', 'avg_longitude']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['allowed_ip_ranges', 'city', 'wifi_ssids']);
        });
    }
};

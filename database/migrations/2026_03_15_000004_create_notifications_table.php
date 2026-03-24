<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // attendance_reminder, checkin_confirm, geofence_alert, report_alert, tampering_alert, daily_summary
            $table->morphs('notifiable'); // employee_id أو admin_id
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->string('channel', 20)->default('database'); // database, email, push, whatsapp
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index('type');
        });

        // إعدادات تفضيلات الإشعارات لكل مستخدم
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->morphs('user'); // admin أو employee
            $table->string('notification_type', 50);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('database_enabled')->default(true);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->timestamps();
            $table->unique(['user_type', 'user_id', 'notification_type'], 'notif_pref_user_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};

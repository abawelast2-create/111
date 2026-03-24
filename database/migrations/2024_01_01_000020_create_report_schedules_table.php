<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('report_type', 50);
            $table->string('frequency', 20)->default('daily');
            $table->string('send_time', 5)->default('08:00');
            $table->string('send_day', 10)->nullable();
            $table->json('recipients');
            $table->json('filters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('admins')->cascadeOnDelete();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};

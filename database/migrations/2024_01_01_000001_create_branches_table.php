<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->decimal('latitude', 10, 8)->default(24.57230700);
            $table->decimal('longitude', 11, 8)->default(46.60255200);
            $table->integer('geofence_radius')->default(500);
            $table->time('work_start_time')->default('08:00:00');
            $table->time('work_end_time')->default('16:00:00');
            $table->time('check_in_start_time')->default('07:00:00');
            $table->time('check_in_end_time')->default('10:00:00');
            $table->time('check_out_start_time')->default('15:00:00');
            $table->time('check_out_end_time')->default('20:00:00');
            $table->integer('checkout_show_before')->default(30);
            $table->boolean('allow_overtime')->default(true);
            $table->integer('overtime_start_after')->default(60);
            $table->integer('overtime_min_duration')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('known_devices', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64);
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->integer('usage_count')->default(1);
            $table->timestamp('first_used_at')->useCurrent();
            $table->timestamp('last_used_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['fingerprint', 'employee_id'], 'uq_fp_emp');
            $table->index('fingerprint');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('known_devices');
    }
};

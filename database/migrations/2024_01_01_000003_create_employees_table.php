<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('job_title', 255);
            $table->string('pin', 10)->unique();
            $table->timestamp('pin_changed_at')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('unique_token', 64)->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('device_fingerprint', 64)->nullable();
            $table->timestamp('device_registered_at')->nullable();
            $table->tinyInteger('device_bind_mode')->default(0);
            $table->integer('security_level')->default(2);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

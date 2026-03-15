<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secret_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('report_text')->nullable();
            $table->string('report_type', 50)->default('violation');
            $table->json('image_paths')->nullable();
            $table->boolean('has_image')->default(false);
            $table->string('image_path', 500)->nullable();
            $table->boolean('has_voice')->default(false);
            $table->string('voice_path', 500)->nullable();
            $table->string('voice_effect', 20)->nullable();
            $table->enum('status', ['new', 'reviewed', 'in_progress', 'resolved', 'dismissed', 'archived'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_reports');
    }
};

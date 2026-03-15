<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tampering_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('case_type', 50);
            $table->text('description')->nullable();
            $table->date('attendance_date')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium');
            $table->json('details_json')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('case_type');
            $table->index('attendance_date');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tampering_cases');
    }
};

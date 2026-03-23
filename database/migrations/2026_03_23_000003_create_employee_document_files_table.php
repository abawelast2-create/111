<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emp_document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('emp_document_groups')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->enum('file_type', ['image', 'pdf'])->default('image');
            $table->string('original_name', 255);
            $table->unsignedInteger('file_size')->default(0);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emp_document_files');
    }
};

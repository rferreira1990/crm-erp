<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('work_daily_report_id')->nullable()->constrained('work_daily_reports')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('original_name', 255);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 160);
            $table->unsignedBigInteger('file_size');
            $table->string('category', 40)->default('document');
            $table->timestamps();

            $table->index(['owner_id', 'work_id', 'created_at']);
            $table->index(['work_id', 'work_daily_report_id']);
            $table->index(['owner_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_files');
    }
};


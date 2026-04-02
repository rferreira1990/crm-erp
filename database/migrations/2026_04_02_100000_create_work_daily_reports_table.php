<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('report_date');
            $table->string('day_status', 30)->default('normal');
            $table->text('work_summary');
            $table->decimal('hours_spent', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('incidents')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'work_id', 'report_date']);
            $table->index(['owner_id', 'day_status']);
            $table->index(['work_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_daily_reports');
    }
};


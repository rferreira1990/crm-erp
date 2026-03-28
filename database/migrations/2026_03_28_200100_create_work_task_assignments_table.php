<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_task_id')->constrained('work_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role_snapshot', 120)->nullable();
            $table->decimal('hourly_cost_snapshot', 14, 2)->default(0);
            $table->decimal('hourly_sale_price_snapshot', 14, 2)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->decimal('labor_cost_total', 14, 2)->default(0);
            $table->decimal('labor_sale_total', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['work_task_id', 'user_id']);
            $table->index(['work_task_id', 'worked_minutes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_task_assignments');
    }
};

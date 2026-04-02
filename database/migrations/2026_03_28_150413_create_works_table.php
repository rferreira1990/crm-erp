<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {/*
        Schema::create('works', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('status', 30)->default('planned');
            $table->string('work_type', 100)->nullable();

            $table->string('location')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 120)->nullable();

            $table->date('start_date_planned')->nullable();
            $table->date('end_date_planned')->nullable();
            $table->date('start_date_actual')->nullable();
            $table->date('end_date_actual')->nullable();

            $table->foreignId('technical_manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['owner_id', 'code']);
            $table->index(['owner_id', 'status']);
            $table->index(['owner_id', 'customer_id']);
            $table->index(['owner_id', 'budget_id']);
            $table->index(['owner_id', 'technical_manager_id']);
            $table->index(['owner_id', 'work_type']);
        });*/
    }

    public function down(): void
    {
        /*Schema::dropIfExists('works');*/
    }
};

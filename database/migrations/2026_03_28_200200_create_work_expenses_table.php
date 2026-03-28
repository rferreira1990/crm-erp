<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->string('type', 40);
            $table->date('expense_date');
            $table->string('description', 255);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supplier_name', 150)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->decimal('qty', 14, 3)->nullable();
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->decimal('total_cost', 14, 2);
            $table->decimal('km', 14, 3)->nullable();
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['work_id', 'expense_date']);
            $table->index(['work_id', 'type']);
            $table->index(['work_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_expenses');
    }
};

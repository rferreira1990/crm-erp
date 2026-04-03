<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_supplier_order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('purchase_supplier_order_id')->constrained('purchase_supplier_orders')->cascadeOnDelete();
            $table->foreignId('purchase_supplier_order_receipt_id')
                ->nullable()
                ->constrained('purchase_supplier_order_receipts')
                ->nullOnDelete();
            $table->string('return_number', 50);
            $table->date('return_date');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_supplier_order_id', 'return_number'], 'psor_return_order_number_unique');
            $table->index(['owner_id', 'purchase_supplier_order_id'], 'psor_return_owner_order_idx');
            $table->index(['owner_id', 'return_date'], 'psor_return_owner_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_supplier_order_returns');
    }
};


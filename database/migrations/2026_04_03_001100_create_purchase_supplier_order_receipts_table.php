<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_supplier_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('purchase_supplier_order_id')->constrained('purchase_supplier_orders')->cascadeOnDelete();
            $table->string('receipt_number', 50);
            $table->date('receipt_date');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_supplier_order_id', 'receipt_number'], 'psor_order_number_unique');
            $table->index(['owner_id', 'purchase_supplier_order_id'], 'psor_owner_order_idx');
            $table->index(['owner_id', 'receipt_date'], 'psor_owner_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_supplier_order_receipts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_supplier_order_returns')) {
            return;
        }

        Schema::create('purchase_supplier_order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id');
            $table->foreignId('purchase_supplier_order_id');
            $table->foreignId('purchase_supplier_order_receipt_id')
                ->nullable()
                ;
            $table->string('return_number', 50);
            $table->date('return_date');
            $table->foreignId('user_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('owner_id', 'psor_return_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('purchase_supplier_order_id', 'psor_return_order_fk')
                ->references('id')
                ->on('purchase_supplier_orders')
                ->cascadeOnDelete();
            $table->foreign('purchase_supplier_order_receipt_id', 'psor_return_receipt_fk')
                ->references('id')
                ->on('purchase_supplier_order_receipts')
                ->nullOnDelete();
            $table->foreign('user_id', 'psor_return_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

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

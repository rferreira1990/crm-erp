<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_supplier_order_return_items')) {
            return;
        }

        Schema::create('purchase_supplier_order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id');
            $table->foreignId('purchase_supplier_order_return_id')
                ;
            $table->foreignId('purchase_supplier_order_item_id')
                ;
            $table->foreignId('item_id')->nullable();
            $table->decimal('quantity_returned', 14, 3);
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->foreign('owner_id', 'psori_return_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('purchase_supplier_order_return_id', 'psori_return_fk')
                ->references('id')
                ->on('purchase_supplier_order_returns')
                ->cascadeOnDelete();
            $table->foreign('purchase_supplier_order_item_id', 'psori_return_order_item_fk')
                ->references('id')
                ->on('purchase_supplier_order_items')
                ->cascadeOnDelete();
            $table->foreign('item_id', 'psori_return_item_fk')
                ->references('id')
                ->on('items')
                ->nullOnDelete();

            $table->index(
                ['purchase_supplier_order_return_id', 'purchase_supplier_order_item_id'],
                'psori_return_item_idx'
            );
            $table->index(['owner_id', 'item_id'], 'psori_return_owner_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_supplier_order_return_items');
    }
};

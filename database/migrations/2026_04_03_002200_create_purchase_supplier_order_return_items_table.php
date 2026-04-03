<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_supplier_order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('purchase_supplier_order_return_id')
                ->constrained('purchase_supplier_order_returns')
                ->cascadeOnDelete();
            $table->foreignId('purchase_supplier_order_item_id')
                ->constrained('purchase_supplier_order_items')
                ->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->decimal('quantity_returned', 14, 3);
            $table->string('reason', 255)->nullable();
            $table->timestamps();

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


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_supplier_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_supplier_order_id')->constrained('purchase_supplier_orders')->cascadeOnDelete();
            $table->foreignId('purchase_request_item_id')->constrained('purchase_request_items')->cascadeOnDelete();
            $table->foreignId('purchase_quote_item_id')->nullable()->constrained('purchase_quote_items')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description', 255);
            $table->string('unit_snapshot', 100)->nullable();
            $table->string('supplier_item_reference', 120)->nullable();
            $table->decimal('qty', 14, 3);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('discount_percent', 7, 3)->nullable();
            $table->decimal('line_total', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['purchase_supplier_order_id', 'sort_order'], 'psoi_order_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_supplier_order_items');
    }
};


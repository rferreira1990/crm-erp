<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_quote_id')->constrained('purchase_quotes')->cascadeOnDelete();
            $table->foreignId('purchase_request_item_id')->constrained('purchase_request_items')->cascadeOnDelete();
            $table->decimal('quoted_qty', 14, 3)->nullable();
            $table->decimal('unit_price', 14, 4)->nullable();
            $table->decimal('discount_percent', 7, 3)->nullable();
            $table->decimal('line_total', 14, 2)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['purchase_quote_id', 'purchase_request_item_id'], 'pqi_quote_request_item_unique');
            $table->index(['purchase_request_item_id', 'unit_price'], 'pqi_request_item_price_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_quote_items');
    }
};

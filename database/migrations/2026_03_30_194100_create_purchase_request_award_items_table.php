<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_award_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained('purchase_request_awards')->cascadeOnDelete();
            $table->foreignId('purchase_request_item_id')->constrained('purchase_request_items')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('purchase_quote_id')->nullable()->constrained('purchase_quotes')->nullOnDelete();
            $table->foreignId('purchase_quote_item_id')->nullable()->constrained('purchase_quote_items')->nullOnDelete();
            $table->decimal('awarded_qty', 14, 3);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('discount_percent', 7, 3)->nullable();
            $table->decimal('line_total', 14, 2)->nullable();
            $table->string('supplier_item_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->string('tie_break_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['award_id', 'purchase_request_item_id'], 'prai_award_request_item_unique');
            $table->index(['award_id', 'supplier_id'], 'prai_award_supplier_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_award_items');
    }
};


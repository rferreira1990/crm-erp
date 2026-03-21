<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Linhas do orçamento.
     *
     * Guarda snapshot do artigo no momento em que foi adicionado,
     * para garantir histórico e estabilidade dos valores.
     */
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('budget_id')
                ->constrained('budgets')
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            // Snapshot do item
            $table->string('item_code', 50)->nullable();
            $table->string('item_name', 255);
            $table->enum('item_type', ['product', 'service'])->default('product');
            $table->text('description')->nullable();
            $table->string('unit_name', 100)->nullable();

            // Snapshot fiscal
            $table->foreignId('tax_rate_id')
                ->nullable()
                ->constrained('tax_rates')
                ->nullOnDelete();

            $table->string('tax_rate_name', 100)->nullable();
            $table->decimal('tax_percent', 5, 2)->default(0);

            // Quantidades e preços
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);

            // Totais da linha
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->timestamps();

            $table->index(['budget_id', 'sort_order']);
            $table->index(['item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};

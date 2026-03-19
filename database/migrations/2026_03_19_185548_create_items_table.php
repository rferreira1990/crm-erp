<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            // Identificação
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('short_name', 120)->nullable();
            $table->enum('type', ['product', 'service'])->default('product');
            $table->text('description')->nullable();

            // Classificação
            $table->foreignId('family_id')->nullable()->constrained('item_families')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();

            // Referências
            $table->string('barcode', 100)->nullable()->index();
            $table->string('supplier_reference', 120)->nullable();

            // Preços
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('max_discount_percent', 5, 2)->nullable();

            // Stock
            $table->boolean('tracks_stock')->default(true);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('max_stock', 12, 3)->nullable();
            $table->boolean('stock_alert')->default(false);

            // Website / imagem
            $table->string('image_path', 255)->nullable();
            $table->string('website_short_description', 255)->nullable();
            $table->text('website_long_description')->nullable();
            $table->decimal('online_weight', 10, 3)->nullable();

            // Estado / auditoria
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
            $table->index(['family_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

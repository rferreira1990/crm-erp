<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_direct_purchase_items')) {
            return;
        }

        Schema::create('purchase_direct_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('purchase_direct_purchase_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('tax_rate_id');
            $table->string('description_snapshot', 255);
            $table->string('unit_snapshot', 100)->nullable();
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('vat_percent', 7, 3)->default(0);
            $table->decimal('line_subtotal', 14, 2);
            $table->decimal('line_vat_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('owner_id', 'pdpi_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('purchase_direct_purchase_id', 'pdpi_purchase_fk')
                ->references('id')
                ->on('purchase_direct_purchases')
                ->cascadeOnDelete();

            $table->foreign('item_id', 'pdpi_item_fk')
                ->references('id')
                ->on('items')
                ->restrictOnDelete();

            $table->foreign('tax_rate_id', 'pdpi_tax_rate_fk')
                ->references('id')
                ->on('tax_rates')
                ->restrictOnDelete();

            $table->index(['purchase_direct_purchase_id', 'sort_order'], 'pdpi_purchase_sort_idx');
            $table->index(['owner_id', 'item_id'], 'pdpi_owner_item_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_direct_purchase_items')) {
            return;
        }

        Schema::table('purchase_direct_purchase_items', function (Blueprint $table) {
            $table->dropForeign('pdpi_tax_rate_fk');
            $table->dropForeign('pdpi_item_fk');
            $table->dropForeign('pdpi_purchase_fk');
            $table->dropForeign('pdpi_owner_fk');

            $table->dropIndex('pdpi_purchase_sort_idx');
            $table->dropIndex('pdpi_owner_item_idx');
        });

        Schema::dropIfExists('purchase_direct_purchase_items');
    }
};


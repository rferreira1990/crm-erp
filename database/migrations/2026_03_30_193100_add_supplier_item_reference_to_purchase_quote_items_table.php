<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_quote_items', function (Blueprint $table) {
            $table->string('supplier_item_reference', 120)
                ->nullable()
                ->after('purchase_request_item_id');

            $table->index(['purchase_request_item_id', 'supplier_item_reference'], 'pqi_request_item_supplier_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_quote_items', function (Blueprint $table) {
            $table->dropIndex('pqi_request_item_supplier_reference_index');
            $table->dropColumn('supplier_item_reference');
        });
    }
};


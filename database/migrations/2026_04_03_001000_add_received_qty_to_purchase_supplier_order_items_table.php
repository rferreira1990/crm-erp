<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_supplier_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_supplier_order_items', 'received_qty')) {
                $table->decimal('received_qty', 14, 3)
                    ->default(0)
                    ->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_supplier_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_supplier_order_items', 'received_qty')) {
                $table->dropColumn('received_qty');
            }
        });
    }
};

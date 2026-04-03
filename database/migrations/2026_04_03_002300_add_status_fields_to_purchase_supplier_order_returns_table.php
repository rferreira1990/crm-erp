<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_supplier_order_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_supplier_order_returns', 'status')) {
                $table->string('status', 20)
                    ->default('open')
                    ->after('notes');
                $table->index(['owner_id', 'status'], 'psor_owner_status_idx');
            }

            if (! Schema::hasColumn('purchase_supplier_order_returns', 'closed_at')) {
                $table->timestamp('closed_at')
                    ->nullable()
                    ->after('status');
            }

            if (! Schema::hasColumn('purchase_supplier_order_returns', 'closed_by')) {
                $table->foreignId('closed_by')
                    ->nullable()
                    ->after('closed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_supplier_order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_supplier_order_returns', 'closed_by')) {
                $table->dropConstrainedForeignId('closed_by');
            }

            if (Schema::hasColumn('purchase_supplier_order_returns', 'closed_at')) {
                $table->dropColumn('closed_at');
            }

            if (Schema::hasColumn('purchase_supplier_order_returns', 'status')) {
                $table->dropIndex('psor_owner_status_idx');
                $table->dropColumn('status');
            }
        });
    }
};


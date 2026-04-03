<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_supplier_order_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_supplier_order_returns', 'supplier_confirmation_status')) {
                $table->string('supplier_confirmation_status', 20)
                    ->default('pending')
                    ->after('closed_by');
                $table->index(['owner_id', 'supplier_confirmation_status'], 'psor_owner_confirmation_idx');
            }

            if (! Schema::hasColumn('purchase_supplier_order_returns', 'confirmation_at')) {
                $table->timestamp('confirmation_at')
                    ->nullable()
                    ->after('supplier_confirmation_status');
            }

            if (! Schema::hasColumn('purchase_supplier_order_returns', 'confirmed_by')) {
                $table->foreignId('confirmed_by')
                    ->nullable()
                    ->after('confirmation_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('purchase_supplier_order_returns', 'confirmation_notes')) {
                $table->text('confirmation_notes')
                    ->nullable()
                    ->after('confirmed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_supplier_order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_supplier_order_returns', 'confirmation_notes')) {
                $table->dropColumn('confirmation_notes');
            }

            if (Schema::hasColumn('purchase_supplier_order_returns', 'confirmed_by')) {
                $table->dropConstrainedForeignId('confirmed_by');
            }

            if (Schema::hasColumn('purchase_supplier_order_returns', 'confirmation_at')) {
                $table->dropColumn('confirmation_at');
            }

            if (Schema::hasColumn('purchase_supplier_order_returns', 'supplier_confirmation_status')) {
                $table->dropIndex('psor_owner_confirmation_idx');
                $table->dropColumn('supplier_confirmation_status');
            }
        });
    }
};


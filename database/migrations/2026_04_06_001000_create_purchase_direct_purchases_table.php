<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_direct_purchases')) {
            return;
        }

        Schema::create('purchase_direct_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('document_number', 40);
            $table->date('purchase_date');
            $table->string('external_reference', 120)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 20)->default('posted');
            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('owner_id', 'pdp_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('supplier_id', 'pdp_supplier_fk')
                ->references('id')
                ->on('suppliers')
                ->restrictOnDelete();

            $table->foreign('created_by', 'pdp_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'pdp_updated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['owner_id', 'document_number'], 'pdp_owner_doc_unq');
            $table->index(['owner_id', 'purchase_date'], 'pdp_owner_date_idx');
            $table->index(['owner_id', 'supplier_id'], 'pdp_owner_supplier_idx');
            $table->index(['owner_id', 'status'], 'pdp_owner_status_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_direct_purchases')) {
            return;
        }

        Schema::table('purchase_direct_purchases', function (Blueprint $table) {
            $table->dropForeign('pdp_updated_by_fk');
            $table->dropForeign('pdp_created_by_fk');
            $table->dropForeign('pdp_supplier_fk');
            $table->dropForeign('pdp_owner_fk');

            $table->dropUnique('pdp_owner_doc_unq');
            $table->dropIndex('pdp_owner_date_idx');
            $table->dropIndex('pdp_owner_supplier_idx');
            $table->dropIndex('pdp_owner_status_idx');
        });

        Schema::dropIfExists('purchase_direct_purchases');
    }
};


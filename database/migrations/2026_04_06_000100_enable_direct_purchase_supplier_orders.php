<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_supplier_orders')) {
            return;
        }

        Schema::table('purchase_supplier_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_supplier_orders', 'owner_id')) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('purchase_supplier_orders', 'source_type')) {
                $table->string('source_type', 20)->default('rfq')->after('award_id');
            }
        });

        DB::table('purchase_supplier_orders')
            ->whereNull('source_type')
            ->update(['source_type' => 'rfq']);

        Schema::table('purchase_supplier_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_request_id')->nullable()->change();
            $table->unsignedBigInteger('award_id')->nullable()->change();
        });

        DB::statement('UPDATE purchase_supplier_orders pso INNER JOIN purchase_requests pr ON pr.id = pso.purchase_request_id SET pso.owner_id = pr.owner_id WHERE pso.owner_id IS NULL AND pr.owner_id IS NOT NULL');

        DB::statement('UPDATE purchase_supplier_orders SET owner_id = prepared_by WHERE owner_id IS NULL AND prepared_by IS NOT NULL');

        $missingOwnerCount = (int) DB::table('purchase_supplier_orders')
            ->whereNull('owner_id')
            ->count();

        if ($missingOwnerCount > 0) {
            throw new RuntimeException('Existem encomendas sem owner_id. Corrige os dados antes de continuar a migracao.');
        }

        Schema::table('purchase_supplier_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();

            $table->foreign('owner_id', 'pso_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['owner_id', 'status'], 'pso_owner_status_index');
            $table->index(['owner_id', 'source_type'], 'pso_owner_source_index');
        });

        if (! Schema::hasTable('purchase_supplier_order_items')) {
            return;
        }

        Schema::table('purchase_supplier_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_request_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_supplier_order_items')) {
            $hasNullOrderItemLink = DB::table('purchase_supplier_order_items')
                ->whereNull('purchase_request_item_id')
                ->exists();

            if (! $hasNullOrderItemLink) {
                Schema::table('purchase_supplier_order_items', function (Blueprint $table) {
                    $table->unsignedBigInteger('purchase_request_item_id')->nullable(false)->change();
                });
            }
        }

        if (! Schema::hasTable('purchase_supplier_orders')) {
            return;
        }

        Schema::table('purchase_supplier_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_supplier_orders', 'owner_id')) {
                $table->dropForeign('pso_owner_fk');
            }

            $table->dropIndex('pso_owner_status_index');
            $table->dropIndex('pso_owner_source_index');
        });

        $hasNullPurchaseRequest = DB::table('purchase_supplier_orders')
            ->whereNull('purchase_request_id')
            ->exists();

        $hasNullAward = DB::table('purchase_supplier_orders')
            ->whereNull('award_id')
            ->exists();

        Schema::table('purchase_supplier_orders', function (Blueprint $table) use ($hasNullPurchaseRequest, $hasNullAward) {
            if (! $hasNullPurchaseRequest) {
                $table->unsignedBigInteger('purchase_request_id')->nullable(false)->change();
            }

            if (! $hasNullAward) {
                $table->unsignedBigInteger('award_id')->nullable(false)->change();
            }

            if (Schema::hasColumn('purchase_supplier_orders', 'source_type')) {
                $table->dropColumn('source_type');
            }

            if (Schema::hasColumn('purchase_supplier_orders', 'owner_id')) {
                $table->dropColumn('owner_id');
            }
        });
    }
};


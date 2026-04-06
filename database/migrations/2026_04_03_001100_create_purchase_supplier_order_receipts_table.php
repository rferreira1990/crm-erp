<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_supplier_order_receipts')) {
            Schema::create('purchase_supplier_order_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id');
                $table->foreignId('purchase_supplier_order_id');
                $table->string('receipt_number', 50);
                $table->date('receipt_date');
                $table->foreignId('user_id');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('owner_id', 'psor_owner_fk')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
                $table->foreign('purchase_supplier_order_id', 'psor_order_fk')
                    ->references('id')
                    ->on('purchase_supplier_orders')
                    ->cascadeOnDelete();
                $table->foreign('user_id', 'psor_user_fk')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();

                $table->unique(['purchase_supplier_order_id', 'receipt_number'], 'psor_order_number_unique');
                $table->index(['owner_id', 'purchase_supplier_order_id'], 'psor_owner_order_idx');
                $table->index(['owner_id', 'receipt_date'], 'psor_owner_date_idx');
            });

            return;
        }

        Schema::table('purchase_supplier_order_receipts', function (Blueprint $table) {
            if (! $this->hasIndex('purchase_supplier_order_receipts', 'psor_order_number_unique')) {
                $table->unique(['purchase_supplier_order_id', 'receipt_number'], 'psor_order_number_unique');
            }

            if (! $this->hasIndex('purchase_supplier_order_receipts', 'psor_owner_order_idx')) {
                $table->index(['owner_id', 'purchase_supplier_order_id'], 'psor_owner_order_idx');
            }

            if (! $this->hasIndex('purchase_supplier_order_receipts', 'psor_owner_date_idx')) {
                $table->index(['owner_id', 'receipt_date'], 'psor_owner_date_idx');
            }

            if (! $this->hasForeignKey('purchase_supplier_order_receipts', 'psor_owner_fk')
                && ! $this->hasForeignKey('purchase_supplier_order_receipts', 'purchase_supplier_order_receipts_owner_id_foreign')) {
                $table->foreign('owner_id', 'psor_owner_fk')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            }

            if (! $this->hasForeignKey('purchase_supplier_order_receipts', 'psor_order_fk')) {
                $table->foreign('purchase_supplier_order_id', 'psor_order_fk')
                    ->references('id')
                    ->on('purchase_supplier_orders')
                    ->cascadeOnDelete();
            }

            if (! $this->hasForeignKey('purchase_supplier_order_receipts', 'psor_user_fk')) {
                $table->foreign('user_id', 'psor_user_fk')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_supplier_order_receipts')) {
            return;
        }

        Schema::table('purchase_supplier_order_receipts', function (Blueprint $table) {
            if ($this->hasForeignKey('purchase_supplier_order_receipts', 'psor_user_fk')) {
                $table->dropForeign('psor_user_fk');
            }

            if ($this->hasForeignKey('purchase_supplier_order_receipts', 'psor_order_fk')) {
                $table->dropForeign('psor_order_fk');
            }

            if ($this->hasForeignKey('purchase_supplier_order_receipts', 'psor_owner_fk')) {
                $table->dropForeign('psor_owner_fk');
            }

            if ($this->hasIndex('purchase_supplier_order_receipts', 'psor_owner_date_idx')) {
                $table->dropIndex('psor_owner_date_idx');
            }

            if ($this->hasIndex('purchase_supplier_order_receipts', 'psor_owner_order_idx')) {
                $table->dropIndex('psor_owner_order_idx');
            }

            if ($this->hasIndex('purchase_supplier_order_receipts', 'psor_order_number_unique')) {
                $table->dropUnique('psor_order_number_unique');
            }
        });

        Schema::dropIfExists('purchase_supplier_order_receipts');
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        $dbName = (string) DB::getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$dbName, $tableName, $indexName]
        );

        return $row !== null;
    }

    private function hasForeignKey(string $tableName, string $constraintName): bool
    {
        $dbName = (string) DB::getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_type = ? AND constraint_name = ? LIMIT 1',
            [$dbName, $tableName, 'FOREIGN KEY', $constraintName]
        );

        return $row !== null;
    }
};

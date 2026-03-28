<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'source_type')) {
                $table->string('source_type', 60)
                    ->nullable()
                    ->after('occurred_at');
            }

            if (! Schema::hasColumn('stock_movements', 'source_id')) {
                $table->unsignedBigInteger('source_id')
                    ->nullable()
                    ->after('source_type');
            }

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            try {
                $table->dropIndex('stock_movements_source_type_source_id_index');
            } catch (\Throwable $exception) {
            }

            if (Schema::hasColumn('stock_movements', 'source_id')) {
                $table->dropColumn('source_id');
            }

            if (Schema::hasColumn('stock_movements', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};


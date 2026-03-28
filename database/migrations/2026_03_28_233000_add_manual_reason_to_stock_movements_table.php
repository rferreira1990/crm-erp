<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'manual_reason')) {
                $table->string('manual_reason', 80)
                    ->nullable()
                    ->after('source_id');

                $table->index('manual_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            try {
                $table->dropIndex('stock_movements_manual_reason_index');
            } catch (\Throwable $exception) {
            }

            if (Schema::hasColumn('stock_movements', 'manual_reason')) {
                $table->dropColumn('manual_reason');
            }
        });
    }
};


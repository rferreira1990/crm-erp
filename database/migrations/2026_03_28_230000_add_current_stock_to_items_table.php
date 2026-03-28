<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'current_stock')) {
                $table->decimal('current_stock', 14, 3)
                    ->default(0)
                    ->after('max_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'current_stock')) {
                $table->dropColumn('current_stock');
            }
        });
    }
};


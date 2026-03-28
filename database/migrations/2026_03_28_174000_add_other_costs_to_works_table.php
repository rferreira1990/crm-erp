<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('works', 'other_costs')) {
            Schema::table('works', function (Blueprint $table) {
                $table->decimal('other_costs', 14, 2)
                    ->default(0)
                    ->after('internal_notes');
            });
        }

        DB::table('works')
            ->whereNull('other_costs')
            ->update(['other_costs' => 0]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('works', 'other_costs')) {
            Schema::table('works', function (Blueprint $table) {
                $table->dropColumn('other_costs');
            });
        }
    }
};

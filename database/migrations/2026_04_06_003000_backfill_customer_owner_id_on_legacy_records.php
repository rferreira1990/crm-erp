<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $fallbackOwnerId = DB::table('users')->min('id');

        DB::table('customers')
            ->whereNull('owner_id')
            ->whereNotNull('created_by')
            ->update([
                'owner_id' => DB::raw('created_by'),
            ]);

        if ($fallbackOwnerId !== null) {
            DB::table('customers')
                ->whereNull('owner_id')
                ->update([
                    'owner_id' => (int) $fallbackOwnerId,
                ]);
        }
    }

    public function down(): void
    {
        // Sem rollback seguro: esta migration corrige dados legados.
    }
};

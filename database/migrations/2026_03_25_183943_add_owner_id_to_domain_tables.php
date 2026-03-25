<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'customers',
            'budgets',
            'items',
            'brands',
            'item_families',
            'units',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('owner_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index('owner_id');
            });
        }

        $defaultOwnerId = DB::table('users')->orderBy('id')->value('id');

        if (! $defaultOwnerId) {
            return;
        }

        if (Schema::hasColumn('customers', 'created_by')) {
            DB::table('customers')
                ->whereNull('owner_id')
                ->update([
                    'owner_id' => DB::raw("COALESCE(created_by, {$defaultOwnerId})"),
                ]);
        } else {
            DB::table('customers')
                ->whereNull('owner_id')
                ->update(['owner_id' => $defaultOwnerId]);
        }

        if (Schema::hasColumn('budgets', 'created_by')) {
            DB::table('budgets')
                ->whereNull('owner_id')
                ->update([
                    'owner_id' => DB::raw("COALESCE(created_by, {$defaultOwnerId})"),
                ]);
        } else {
            DB::table('budgets')
                ->whereNull('owner_id')
                ->update(['owner_id' => $defaultOwnerId]);
        }

        if (Schema::hasColumn('items', 'created_by') && Schema::hasColumn('items', 'updated_by')) {
            DB::table('items')
                ->whereNull('owner_id')
                ->update([
                    'owner_id' => DB::raw("COALESCE(created_by, updated_by, {$defaultOwnerId})"),
                ]);
        } else {
            DB::table('items')
                ->whereNull('owner_id')
                ->update(['owner_id' => $defaultOwnerId]);
        }

        DB::table('brands')
            ->whereNull('owner_id')
            ->update(['owner_id' => $defaultOwnerId]);

        DB::table('item_families')
            ->whereNull('owner_id')
            ->update(['owner_id' => $defaultOwnerId]);

        // Units ficam globais por defeito.
        // owner_id = null => comum a todos
        // owner_id = X => unidade personalizada do utilizador X
    }

    public function down(): void
    {
        $tables = [
            'customers',
            'budgets',
            'items',
            'brands',
            'item_families',
            'units',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign([$tableName === 'users' ? 'owner_id' : 'owner_id']);
                $table->dropIndex([$tableName === 'users' ? 'owner_id' : 'owner_id']);
                $table->dropColumn('owner_id');
            });
        }
    }
};

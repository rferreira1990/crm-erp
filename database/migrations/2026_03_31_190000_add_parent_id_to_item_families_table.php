<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('item_families', 'parent_id')) {
            Schema::table('item_families', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('owner_id')
                    ->constrained('item_families')
                    ->nullOnDelete();
            });
        }

        $this->dropUniqueIfExists('item_families', 'item_families_name_unique');

        if (! $this->indexExists('item_families', 'item_families_parent_id_name_unique')) {
            Schema::table('item_families', function (Blueprint $table) {
                $table->unique(['parent_id', 'name'], 'item_families_parent_id_name_unique');
            });
        }
    }

    public function down(): void
    {
        $this->dropUniqueIfExists('item_families', 'item_families_parent_id_name_unique');

        if (Schema::hasColumn('item_families', 'parent_id')) {
            Schema::table('item_families', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }

        if (! $this->indexExists('item_families', 'item_families_name_unique')) {
            Schema::table('item_families', function (Blueprint $table) {
                $table->unique('name', 'item_families_name_unique');
            });
        }
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName) {
            $table->dropUnique($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};

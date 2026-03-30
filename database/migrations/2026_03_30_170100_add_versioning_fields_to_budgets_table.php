<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreignId('root_budget_id')
                ->nullable()
                ->after('owner_id')
                ->constrained('budgets')
                ->nullOnDelete();

            $table->foreignId('parent_budget_id')
                ->nullable()
                ->after('root_budget_id')
                ->constrained('budgets')
                ->nullOnDelete();

            $table->unsignedInteger('version_number')
                ->default(1)
                ->after('parent_budget_id');

            $table->index(['root_budget_id', 'version_number'], 'budgets_root_version_index');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropIndex('budgets_root_version_index');
            $table->dropConstrainedForeignId('parent_budget_id');
            $table->dropConstrainedForeignId('root_budget_id');
            $table->dropColumn('version_number');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addOwnerIdColumn('tax_exemption_reasons');
        $this->addOwnerIdColumn('tax_rates');
    }

    public function down(): void
    {
        $this->dropOwnerIdColumn('tax_rates');
        $this->dropOwnerIdColumn('tax_exemption_reasons');
    }

    private function addOwnerIdColumn(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'owner_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->foreignId('owner_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            $blueprint->index('owner_id');
        });
    }

    private function dropOwnerIdColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'owner_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['owner_id']);
            $blueprint->dropIndex(['owner_id']);
            $blueprint->dropColumn('owner_id');
        });
    }
};


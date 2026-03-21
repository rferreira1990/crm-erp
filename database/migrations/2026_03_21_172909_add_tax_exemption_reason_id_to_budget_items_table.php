<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->foreignId('tax_exemption_reason_id')
                ->nullable()
                ->after('tax_percent')
                ->constrained('tax_exemption_reasons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_exemption_reason_id');
        });
    }
};

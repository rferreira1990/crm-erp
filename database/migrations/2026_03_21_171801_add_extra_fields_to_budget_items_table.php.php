<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->string('tax_exemption_reason')->nullable()->after('tax_percent');
            $table->text('notes')->nullable()->after('tax_exemption_reason');
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropColumn(['tax_exemption_reason', 'notes']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->date('valid_until')->nullable()->after('budget_date');
            $table->string('external_reference', 255)->nullable()->after('project_name');
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn([
                'valid_until',
                'external_reference',
            ]);
        });
    }
};

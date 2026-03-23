<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona campos reais de cabeçalho ao orçamento.
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->string('designation')->nullable()->after('code');
            $table->date('budget_date')->nullable()->after('status');
            $table->string('zone')->nullable()->after('budget_date');
            $table->string('project_name')->nullable()->after('zone');
        });
    }

    /**
     * Reverte os campos adicionados ao orçamento.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn([
                'designation',
                'budget_date',
                'zone',
                'project_name',
            ]);
        });
    }
};

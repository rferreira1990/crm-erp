<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Atualiza a tabela de orçamentos para suportar
     * totais detalhados e auditoria futura.
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->after('status');
            $table->decimal('discount_total', 12, 2)->default(0)->after('subtotal');
            $table->decimal('tax_total', 12, 2)->default(0)->after('discount_total');

            $table->decimal('total', 12, 2)->default(0)->change();

            $table->foreignId('updated_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverte alterações adicionais ao budget.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'subtotal',
                'discount_total',
                'tax_total',
            ]);
        });
    }
};

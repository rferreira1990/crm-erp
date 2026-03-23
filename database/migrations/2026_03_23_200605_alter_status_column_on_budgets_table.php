<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Permitir temporariamente estados antigos e novos
        DB::statement("
            ALTER TABLE budgets
            MODIFY status ENUM(
                'draft',
                'sent',
                'approved',
                'rejected',
                'created',
                'waiting_response',
                'accepted'
            ) NOT NULL DEFAULT 'draft'
        ");

        // 2) Converter dados antigos para a nova lógica
        DB::statement("
            UPDATE budgets
            SET status = 'accepted'
            WHERE status = 'approved'
        ");

        // Se quiseres, podes decidir o que fazer com estados inválidos/estranhos
        DB::statement("
            UPDATE budgets
            SET status = 'draft'
            WHERE status NOT IN (
                'draft',
                'created',
                'sent',
                'waiting_response',
                'accepted',
                'rejected'
            )
        ");

        // 3) Fechar o enum só com os estados finais
        DB::statement("
            ALTER TABLE budgets
            MODIFY status ENUM(
                'draft',
                'created',
                'sent',
                'waiting_response',
                'accepted',
                'rejected'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        // 1) Reabrir temporariamente para aceitar antigos e novos
        DB::statement("
            ALTER TABLE budgets
            MODIFY status ENUM(
                'draft',
                'sent',
                'approved',
                'rejected',
                'created',
                'waiting_response',
                'accepted'
            ) NOT NULL DEFAULT 'draft'
        ");

        // 2) Reverter dados
        DB::statement("
            UPDATE budgets
            SET status = 'approved'
            WHERE status = 'accepted'
        ");

        DB::statement("
            UPDATE budgets
            SET status = 'sent'
            WHERE status IN ('created', 'waiting_response')
        ");

        // 3) Voltar ao enum antigo
        DB::statement("
            ALTER TABLE budgets
            MODIFY status ENUM(
                'draft',
                'sent',
                'approved',
                'rejected'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};

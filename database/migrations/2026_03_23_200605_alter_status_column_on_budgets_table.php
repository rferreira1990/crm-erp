<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
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
        }

        DB::statement("
            UPDATE budgets
            SET status = 'accepted'
            WHERE status = 'approved'
        ");

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

        if ($driver === 'mysql') {
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
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
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
        }

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

        if ($driver === 'mysql') {
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
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('works')) {
            Schema::table('works', function (Blueprint $table) {
                $table->index('status', 'works_status_index');
                $table->index(['status', 'technical_manager_id'], 'works_status_technical_manager_index');
                $table->index(['status', 'end_date_actual'], 'works_status_end_date_actual_index');
            });
        }

        if (Schema::hasTable('work_tasks')) {
            Schema::table('work_tasks', function (Blueprint $table) {
                $table->index('status', 'work_tasks_status_index');
            });
        }

        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                $table->index(['is_active', 'tracks_stock', 'current_stock'], 'items_active_tracks_current_stock_index');
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->index(['occurred_at', 'id'], 'stock_movements_occurred_at_id_index');
                $table->index(['direction', 'occurred_at'], 'stock_movements_direction_occurred_at_index');
                $table->index(['source_type', 'occurred_at'], 'stock_movements_source_type_occurred_at_index');
                $table->index(['movement_type', 'occurred_at'], 'stock_movements_movement_type_occurred_at_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('works')) {
            Schema::table('works', function (Blueprint $table) {
                try {
                    $table->dropIndex('works_status_index');
                } catch (\Throwable $exception) {
                }

                try {
                    $table->dropIndex('works_status_technical_manager_index');
                } catch (\Throwable $exception) {
                }

                try {
                    $table->dropIndex('works_status_end_date_actual_index');
                } catch (\Throwable $exception) {
                }
            });
        }

        if (Schema::hasTable('work_tasks')) {
            Schema::table('work_tasks', function (Blueprint $table) {
                try {
                    $table->dropIndex('work_tasks_status_index');
                } catch (\Throwable $exception) {
                }
            });
        }

        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                try {
                    $table->dropIndex('items_active_tracks_current_stock_index');
                } catch (\Throwable $exception) {
                }
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                try {
                    $table->dropIndex('stock_movements_occurred_at_id_index');
                } catch (\Throwable $exception) {
                }

                try {
                    $table->dropIndex('stock_movements_direction_occurred_at_index');
                } catch (\Throwable $exception) {
                }

                try {
                    $table->dropIndex('stock_movements_source_type_occurred_at_index');
                } catch (\Throwable $exception) {
                }

                try {
                    $table->dropIndex('stock_movements_movement_type_occurred_at_index');
                } catch (\Throwable $exception) {
                }
            });
        }
    }
};

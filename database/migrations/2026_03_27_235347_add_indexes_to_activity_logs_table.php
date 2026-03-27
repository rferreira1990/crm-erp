<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['owner_id', 'entity', 'entity_id'], 'activity_logs_owner_entity_entity_id_index');
            $table->index(['owner_id', 'action'], 'activity_logs_owner_action_index');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_owner_entity_entity_id_index');
            $table->dropIndex('activity_logs_owner_action_index');
        });
    }
};

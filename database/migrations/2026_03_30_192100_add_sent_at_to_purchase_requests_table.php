<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->index(['status', 'sent_at'], 'pr_status_sent_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex('pr_status_sent_at_index');
            $table->dropColumn('sent_at');
        });
    }
};

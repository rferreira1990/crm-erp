<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_daily_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('work_daily_report_id')->constrained('work_daily_reports')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('description_snapshot', 255);
            $table->decimal('quantity', 14, 3);
            $table->string('unit_snapshot', 100)->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'work_daily_report_id']);
            $table->index(['work_daily_report_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_daily_report_items');
    }
};


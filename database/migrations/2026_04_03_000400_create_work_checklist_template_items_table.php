<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('work_checklist_template_id')
                ->constrained('work_checklist_templates')
                ->cascadeOnDelete();
            $table->string('description', 500);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['owner_id', 'work_checklist_template_id'], 'wcti_owner_template_idx');
            $table->index(['work_checklist_template_id', 'sort_order'], 'wcti_template_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_checklist_template_items');
    }
};

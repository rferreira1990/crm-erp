<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('work_checklist_items');

        Schema::create('work_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('work_checklist_id')->constrained('work_checklists')->cascadeOnDelete();
            $table->string('description', 500);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'work_checklist_id'], 'wci_owner_checklist_idx');
            $table->index(['work_checklist_id', 'is_required', 'is_completed'], 'wci_req_completed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_checklist_items');
    }
};

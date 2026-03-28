<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->string('description_snapshot', 255);
            $table->string('unit_snapshot', 100)->nullable();
            $table->decimal('qty', 14, 3);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('total_cost', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['work_id', 'item_id']);
            $table->index(['work_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_materials');
    }
};

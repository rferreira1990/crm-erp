<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('work_material_id')->nullable()->constrained('work_materials')->cascadeOnDelete();
            $table->string('movement_type', 40);
            $table->enum('direction', ['in', 'out', 'adjustment'])->default('adjustment');
            $table->decimal('quantity', 14, 3);
            $table->decimal('stock_before', 14, 3);
            $table->decimal('stock_after', 14, 3);
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('work_material_id');
            $table->index(['item_id', 'occurred_at']);
            $table->index(['movement_type', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};


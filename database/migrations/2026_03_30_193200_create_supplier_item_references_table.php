<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_item_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('supplier_item_reference', 120);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'item_id'], 'sir_supplier_item_unique');
            $table->index(['item_id', 'supplier_item_reference'], 'sir_item_reference_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_item_references');
    }
};


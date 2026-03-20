<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();

            $table->string('disk')->default('public');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size')->default(0);

            $table->enum('type', ['image', 'pdf']);
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['item_id', 'type']);
            $table->index(['item_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_files');
    }
};

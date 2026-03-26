<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('document_type'); // budget, invoice, etc
            $table->string('prefix'); // ORC
            $table->string('name'); // 2026
            $table->integer('year');
            $table->unsignedInteger('next_number')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['owner_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_series');
    }
};

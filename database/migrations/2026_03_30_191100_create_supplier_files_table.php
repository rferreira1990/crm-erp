<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('disk', 50)->default('local');
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('type', 30)->default('document');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'type'], 'supplier_files_supplier_type_index');
            $table->index(['supplier_id', 'created_at'], 'supplier_files_supplier_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_files');
    }
};


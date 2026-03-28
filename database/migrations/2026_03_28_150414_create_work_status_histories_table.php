<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();

            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);

            $table->text('notes')->nullable();

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['work_id', 'created_at']);
            $table->index(['new_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_status_histories');
    }
};

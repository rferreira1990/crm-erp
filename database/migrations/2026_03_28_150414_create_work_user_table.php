<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['work_id', 'user_id']);
            $table->index(['user_id', 'work_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_user');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Multi-tenant
            $table->unsignedBigInteger('owner_id')->index();

            // Quem fez a ação
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Tipo de ação (create, update, delete, etc.)
            $table->string('action');

            // Entidade (budget, item, customer...)
            $table->string('entity');

            // ID da entidade
            $table->unsignedBigInteger('entity_id')->nullable()->index();

            // Payload com alterações (json)
            $table->json('payload')->nullable();

            $table->timestamps();

            // Foreign keys (opcional — se quiseres manter flexível podes tirar)
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('message')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['budget_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_email_logs');
    }
};

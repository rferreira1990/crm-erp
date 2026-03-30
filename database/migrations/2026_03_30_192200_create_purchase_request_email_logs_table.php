<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('message')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['purchase_request_id', 'sent_at'], 'prel_request_sent_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_email_logs');
    }
};

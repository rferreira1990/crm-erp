<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_supplier_order_return_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('purchase_supplier_order_return_id')
                ->constrained('purchase_supplier_order_returns')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email');
            $table->string('cc_email')->nullable();
            $table->string('bcc_email')->nullable();
            $table->string('subject');
            $table->text('body_snapshot')->nullable();
            $table->boolean('is_resend')->default(false);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['purchase_supplier_order_return_id', 'sent_at'], 'psorel_return_sent_at_idx');
            $table->index(['owner_id', 'sent_at'], 'psorel_owner_sent_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_supplier_order_return_email_logs');
    }
};


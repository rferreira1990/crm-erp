<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->string('mode', 40);
            $table->foreignId('forced_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('selected_quote_id')->nullable()->constrained('purchase_quotes')->nullOnDelete();
            $table->text('justification')->nullable();
            $table->boolean('allow_partial')->default(false);
            $table->string('status', 30)->default('active');
            $table->json('decision_payload')->nullable();
            $table->unsignedInteger('generated_orders_count')->default(0);
            $table->unsignedInteger('generated_items_count')->default(0);
            $table->timestamp('decided_at');
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('replaced_by_award_id')->nullable()->constrained('purchase_request_awards')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_request_id', 'status'], 'pra_request_status_index');
            $table->index(['purchase_request_id', 'decided_at'], 'pra_request_decided_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_awards');
    }
};


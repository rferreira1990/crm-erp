<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('award_id')->constrained('purchase_request_awards')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('purchase_quote_id')->nullable()->constrained('purchase_quotes')->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 30)->default('prepared');
            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('prepared_at');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['purchase_request_id', 'status'], 'pso_request_status_index');
            $table->index(['award_id', 'supplier_id'], 'pso_award_supplier_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_supplier_orders');
    }
};


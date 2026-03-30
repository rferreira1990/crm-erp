<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('supplier_name_snapshot', 255);
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('payment_term_snapshot', 120)->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 30)->default('received');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['purchase_request_id', 'supplier_id'], 'pq_request_supplier_unique');
            $table->index(['purchase_request_id', 'status', 'total_amount'], 'pq_request_status_total_index');
            $table->index(['purchase_request_id', 'valid_until'], 'pq_request_valid_until_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_quotes');
    }
};


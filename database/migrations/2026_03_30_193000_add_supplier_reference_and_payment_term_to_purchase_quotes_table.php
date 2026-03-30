<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_quotes', function (Blueprint $table) {
            $table->foreignId('payment_term_id')
                ->nullable()
                ->after('payment_term_snapshot')
                ->constrained('payment_terms')
                ->nullOnDelete();

            $table->string('supplier_quote_reference', 120)
                ->nullable()
                ->after('supplier_name_snapshot');

            $table->string('quote_pdf_disk', 50)
                ->nullable()
                ->after('notes');

            $table->string('quote_pdf_path', 255)
                ->nullable()
                ->after('quote_pdf_disk');

            $table->string('quote_pdf_original_name', 255)
                ->nullable()
                ->after('quote_pdf_path');

            $table->string('quote_pdf_mime_type', 120)
                ->nullable()
                ->after('quote_pdf_original_name');

            $table->unsignedBigInteger('quote_pdf_file_size')
                ->nullable()
                ->after('quote_pdf_mime_type');

            $table->index(['purchase_request_id', 'supplier_quote_reference'], 'pq_request_supplier_reference_index');
            $table->index(['payment_term_id'], 'pq_payment_term_index');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_quotes', function (Blueprint $table) {
            $table->dropIndex('pq_request_supplier_reference_index');
            $table->dropIndex('pq_payment_term_index');
            $table->dropConstrainedForeignId('payment_term_id');

            $table->dropColumn([
                'supplier_quote_reference',
                'quote_pdf_disk',
                'quote_pdf_path',
                'quote_pdf_original_name',
                'quote_pdf_mime_type',
                'quote_pdf_file_size',
            ]);
        });
    }
};


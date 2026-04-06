<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_direct_purchases')) {
            return;
        }

        Schema::table('purchase_direct_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_direct_purchases', 'payment_method')) {
                $table->string('payment_method', 100)->nullable()->after('currency');
            }

            if (! Schema::hasColumn('purchase_direct_purchases', 'due_date')) {
                $table->date('due_date')->nullable()->after('purchase_date');
            }

            if (! Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_original_name')) {
                $table->string('invoice_pdf_original_name', 255)->nullable()->after('notes');
            }

            if (! Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_file_name')) {
                $table->string('invoice_pdf_file_name', 255)->nullable()->after('invoice_pdf_original_name');
            }

            if (! Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_path')) {
                $table->string('invoice_pdf_path', 255)->nullable()->after('invoice_pdf_file_name');
            }

            if (! Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_mime_type')) {
                $table->string('invoice_pdf_mime_type', 120)->nullable()->after('invoice_pdf_path');
            }

            if (! Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_size')) {
                $table->unsignedBigInteger('invoice_pdf_size')->nullable()->after('invoice_pdf_mime_type');
            }

            if (! Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_uploaded_at')) {
                $table->timestamp('invoice_pdf_uploaded_at')->nullable()->after('invoice_pdf_size');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_direct_purchases')) {
            return;
        }

        Schema::table('purchase_direct_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_uploaded_at')) {
                $table->dropColumn('invoice_pdf_uploaded_at');
            }
            if (Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_size')) {
                $table->dropColumn('invoice_pdf_size');
            }
            if (Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_mime_type')) {
                $table->dropColumn('invoice_pdf_mime_type');
            }
            if (Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_path')) {
                $table->dropColumn('invoice_pdf_path');
            }
            if (Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_file_name')) {
                $table->dropColumn('invoice_pdf_file_name');
            }
            if (Schema::hasColumn('purchase_direct_purchases', 'invoice_pdf_original_name')) {
                $table->dropColumn('invoice_pdf_original_name');
            }
            if (Schema::hasColumn('purchase_direct_purchases', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('purchase_direct_purchases', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};


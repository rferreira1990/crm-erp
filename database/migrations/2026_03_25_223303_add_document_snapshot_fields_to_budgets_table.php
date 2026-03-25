<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->timestamp('snapshot_generated_at')->nullable()->after('updated_by');

            /*
            |--------------------------------------------------------------------------
            | Snapshot da empresa
            |--------------------------------------------------------------------------
            */
            $table->string('snapshot_company_name', 150)->nullable()->after('snapshot_generated_at');
            $table->string('snapshot_company_address_line_1', 150)->nullable();
            $table->string('snapshot_company_address_line_2', 150)->nullable();
            $table->string('snapshot_company_city', 100)->nullable();
            $table->string('snapshot_company_district', 100)->nullable();
            $table->string('snapshot_company_postal_code', 4)->nullable();
            $table->string('snapshot_company_postal_code_suffix', 3)->nullable();
            $table->string('snapshot_company_postal_designation', 100)->nullable();
            $table->string('snapshot_company_country_code', 5)->nullable();
            $table->string('snapshot_company_tax_number', 20)->nullable();
            $table->string('snapshot_company_phone', 30)->nullable();
            $table->string('snapshot_company_fax', 30)->nullable();
            $table->string('snapshot_company_contact_person', 150)->nullable();
            $table->string('snapshot_company_email', 150)->nullable();
            $table->string('snapshot_company_website', 200)->nullable();
            $table->decimal('snapshot_company_share_capital', 15, 2)->nullable();
            $table->string('snapshot_company_registry_office', 150)->nullable();
            $table->string('snapshot_company_logo_path')->nullable();
            $table->string('snapshot_company_bank_name', 150)->nullable();
            $table->string('snapshot_company_bank_iban', 50)->nullable();
            $table->string('snapshot_company_bank_bic_swift', 20)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Snapshot do cliente
            |--------------------------------------------------------------------------
            */
            $table->string('snapshot_customer_code', 50)->nullable();
            $table->string('snapshot_customer_name', 150)->nullable();
            $table->string('snapshot_customer_nif', 20)->nullable();
            $table->string('snapshot_customer_email', 150)->nullable();
            $table->string('snapshot_customer_phone', 30)->nullable();
            $table->string('snapshot_customer_mobile', 30)->nullable();
            $table->string('snapshot_customer_contact_person', 150)->nullable();
            $table->string('snapshot_customer_address_line_1', 150)->nullable();
            $table->string('snapshot_customer_address_line_2', 150)->nullable();
            $table->string('snapshot_customer_postal_code', 20)->nullable();
            $table->string('snapshot_customer_city', 100)->nullable();
            $table->string('snapshot_customer_country', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_generated_at',

                'snapshot_company_name',
                'snapshot_company_address_line_1',
                'snapshot_company_address_line_2',
                'snapshot_company_city',
                'snapshot_company_district',
                'snapshot_company_postal_code',
                'snapshot_company_postal_code_suffix',
                'snapshot_company_postal_designation',
                'snapshot_company_country_code',
                'snapshot_company_tax_number',
                'snapshot_company_phone',
                'snapshot_company_fax',
                'snapshot_company_contact_person',
                'snapshot_company_email',
                'snapshot_company_website',
                'snapshot_company_share_capital',
                'snapshot_company_registry_office',
                'snapshot_company_logo_path',
                'snapshot_company_bank_name',
                'snapshot_company_bank_iban',
                'snapshot_company_bank_bic_swift',

                'snapshot_customer_code',
                'snapshot_customer_name',
                'snapshot_customer_nif',
                'snapshot_customer_email',
                'snapshot_customer_phone',
                'snapshot_customer_mobile',
                'snapshot_customer_contact_person',
                'snapshot_customer_address_line_1',
                'snapshot_customer_address_line_2',
                'snapshot_customer_postal_code',
                'snapshot_customer_city',
                'snapshot_customer_country',
            ]);
        });
    }
};

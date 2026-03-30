<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('budget_default_pdf_template', 30)
                ->default('commercial')
                ->after('mail_default_bcc');

            $table->string('budget_default_vat_mode', 50)
                ->default('with_vat')
                ->after('budget_default_pdf_template');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'budget_default_pdf_template',
                'budget_default_vat_mode',
            ]);
        });
    }
};

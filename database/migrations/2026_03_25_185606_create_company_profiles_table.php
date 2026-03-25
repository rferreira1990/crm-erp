<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('company_name', 150)->nullable();
            $table->string('address_line_1', 150)->nullable();
            $table->string('address_line_2', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('postal_code', 4)->nullable();
            $table->string('postal_code_suffix', 3)->nullable();
            $table->string('postal_designation', 100)->nullable();
            $table->string('country_code', 5)->nullable()->default('PT');
            $table->string('tax_number', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('fax', 30)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 200)->nullable();
            $table->decimal('share_capital', 15, 2)->nullable();
            $table->string('registry_office', 150)->nullable();
            $table->string('logo_path')->nullable();

            $table->string('bank_name', 150)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->string('bank_bic_swift', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};

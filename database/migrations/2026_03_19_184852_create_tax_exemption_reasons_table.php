<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_exemption_reasons', function (Blueprint $table) {
            $table->id();

            $table->string('code', 10)->unique(); // M01, M07, M99...
            $table->text('description');
            $table->string('invoice_note', 255)->nullable();
            $table->string('legal_reference', 255)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_exemption_reasons');
    }
};

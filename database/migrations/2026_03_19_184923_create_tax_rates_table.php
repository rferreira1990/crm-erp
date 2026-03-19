<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);                 // Taxa normal, Reduzida, Isenta...
            $table->decimal('percent', 5, 2)->default(0);
            $table->string('saft_code', 10);            // NOR, RED, INT, ISE, NS, OUT
            $table->string('country_code', 5)->default('PT');

            $table->boolean('is_exempt')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('exemption_reason_id')
                ->nullable()
                ->constrained('tax_exemption_reasons')
                ->nullOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['country_code', 'saft_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};

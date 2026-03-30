<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('tax_number', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('external_reference', 100)->nullable();

            $table->string('address', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable()->default('Portugal');

            $table->foreignId('payment_term_id')
                ->nullable()
                ->constrained('payment_terms')
                ->nullOnDelete();

            $table->foreignId('default_tax_rate_id')
                ->nullable()
                ->constrained('tax_rates')
                ->nullOnDelete();

            $table->decimal('default_discount_percent', 5, 2)->default(0);
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->decimal('minimum_order_value', 12, 2)->nullable();
            $table->decimal('free_shipping_threshold', 12, 2)->nullable();
            $table->string('preferred_payment_method', 100)->nullable();
            $table->text('default_notes_for_purchases')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->string('habitual_order_email', 150)->nullable();
            $table->string('preferred_contact_method', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'is_active']);
            $table->index(['tax_number']);
            $table->index(['payment_term_id', 'default_tax_rate_id']);
            $table->index(['owner_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};


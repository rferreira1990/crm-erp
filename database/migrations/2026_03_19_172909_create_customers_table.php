<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            /*
             |--------------------------------------------------------------------------
             | Identificação
             |--------------------------------------------------------------------------
             */
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->enum('type', ['private', 'company'])->default('private');
            $table->string('nif', 20)->nullable()->index();

            /*
             |--------------------------------------------------------------------------
             | Contactos
             |--------------------------------------------------------------------------
             */
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('contact_person')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Morada
             |--------------------------------------------------------------------------
             */
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Portugal');

            /*
             |--------------------------------------------------------------------------
             | Informação comercial
             |--------------------------------------------------------------------------
             */
            $table->decimal('default_discount', 5, 2)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(0);

            /*
             |--------------------------------------------------------------------------
             | CRM leve
             |--------------------------------------------------------------------------
             */
            $table->string('source')->nullable();
            $table->enum('status', ['active', 'inactive', 'prospect'])->default('active');
            $table->timestamp('last_contact_at')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Observações e estado
             |--------------------------------------------------------------------------
             */
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            /*
             |--------------------------------------------------------------------------
             | Auditoria básica
             |--------------------------------------------------------------------------
             */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            /*
             |--------------------------------------------------------------------------
             | Índices úteis
             |--------------------------------------------------------------------------
             */
            $table->index(['name', 'type']);
            $table->index(['status', 'is_active']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

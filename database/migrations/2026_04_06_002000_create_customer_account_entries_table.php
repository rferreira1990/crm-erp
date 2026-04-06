<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_account_entries')) {
            return;
        }

        Schema::create('customer_account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id');
            $table->foreignId('customer_id');
            $table->date('entry_date');
            $table->string('type', 20);
            $table->decimal('amount', 14, 2);
            $table->string('description', 255);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('owner_id', 'cae_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('customer_id', 'cae_customer_fk')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'cae_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(['owner_id', 'customer_id', 'entry_date'], 'cae_owner_customer_date_idx');
            $table->index(['owner_id', 'due_date'], 'cae_owner_due_date_idx');
            $table->index(['owner_id', 'type'], 'cae_owner_type_idx');
            $table->index(['reference_type', 'reference_id'], 'cae_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_account_entries');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_account_entries')) {
            return;
        }

        Schema::create('supplier_account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id');
            $table->foreignId('supplier_id');
            $table->date('entry_date');
            $table->string('type', 30);
            $table->decimal('amount', 14, 2);
            $table->string('description', 255);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('owner_id', 'sae_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('supplier_id', 'sae_supplier_fk')
                ->references('id')
                ->on('suppliers')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'sae_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(['owner_id', 'supplier_id', 'entry_date'], 'sae_owner_supplier_date_idx');
            $table->index(['owner_id', 'due_date'], 'sae_owner_due_date_idx');
            $table->index(['owner_id', 'type'], 'sae_owner_type_idx');
            $table->index(['reference_type', 'reference_id'], 'sae_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_account_entries');
    }
};


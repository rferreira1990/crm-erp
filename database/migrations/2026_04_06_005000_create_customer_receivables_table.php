<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_receivables')) {
            return;
        }

        Schema::create('customer_receivables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('document_number', 40);
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('amount', 14, 2);
            $table->string('description', 255);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('owner_id', 'cr_owner_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('customer_id', 'cr_customer_fk')
                ->references('id')
                ->on('customers')
                ->restrictOnDelete();

            $table->foreign('user_id', 'cr_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('issued_by', 'cr_issued_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('closed_by', 'cr_closed_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('created_by', 'cr_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'cr_updated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['owner_id', 'document_number'], 'cr_owner_doc_unq');
            $table->index(['owner_id', 'customer_id', 'issue_date'], 'cr_owner_customer_date_idx');
            $table->index(['owner_id', 'due_date', 'status'], 'cr_owner_due_status_idx');
            $table->index(['owner_id', 'status'], 'cr_owner_status_idx');
            $table->index(['owner_id', 'reference_type', 'reference_id'], 'cr_owner_ref_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_receivables')) {
            return;
        }

        Schema::table('customer_receivables', function (Blueprint $table) {
            $table->dropForeign('cr_updated_by_fk');
            $table->dropForeign('cr_created_by_fk');
            $table->dropForeign('cr_closed_by_fk');
            $table->dropForeign('cr_issued_by_fk');
            $table->dropForeign('cr_user_fk');
            $table->dropForeign('cr_customer_fk');
            $table->dropForeign('cr_owner_fk');

            $table->dropUnique('cr_owner_doc_unq');
            $table->dropIndex('cr_owner_customer_date_idx');
            $table->dropIndex('cr_owner_due_status_idx');
            $table->dropIndex('cr_owner_status_idx');
            $table->dropIndex('cr_owner_ref_idx');
        });

        Schema::dropIfExists('customer_receivables');
    }
};

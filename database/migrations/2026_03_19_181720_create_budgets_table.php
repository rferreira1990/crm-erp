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
       Schema::create('budgets', function (Blueprint $table) {
    $table->id();

    $table->string('code')->unique();

    $table->foreignId('customer_id')->constrained()->cascadeOnDelete();

    $table->enum('status', ['draft', 'sent', 'approved', 'rejected'])->default('draft');

    $table->decimal('total', 10, 2)->default(0);

    $table->text('notes')->nullable();

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

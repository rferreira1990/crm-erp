<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 120)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'hourly_cost')) {
                $table->decimal('hourly_cost', 14, 2)->default(0)->after('job_title');
            }

            if (! Schema::hasColumn('users', 'hourly_sale_price')) {
                $table->decimal('hourly_sale_price', 14, 2)->nullable()->after('hourly_cost');
            }

            if (! Schema::hasColumn('users', 'is_labor_enabled')) {
                $table->boolean('is_labor_enabled')->default(true)->after('hourly_sale_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_labor_enabled')) {
                $table->dropColumn('is_labor_enabled');
            }

            if (Schema::hasColumn('users', 'hourly_sale_price')) {
                $table->dropColumn('hourly_sale_price');
            }

            if (Schema::hasColumn('users', 'hourly_cost')) {
                $table->dropColumn('hourly_cost');
            }

            if (Schema::hasColumn('users', 'job_title')) {
                $table->dropColumn('job_title');
            }
        });
    }
};

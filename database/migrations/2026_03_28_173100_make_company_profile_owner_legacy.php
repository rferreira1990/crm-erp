<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_profiles') || ! Schema::hasColumn('company_profiles', 'owner_id')) {
            return;
        }

        Schema::table('company_profiles', function (Blueprint $table) {
            try {
                $table->dropForeign(['owner_id']);
            } catch (Throwable) {
                //
            }

            try {
                $table->dropUnique('company_profiles_owner_id_unique');
            } catch (Throwable) {
                //
            }
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable()->change();
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->index('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_profiles') || ! Schema::hasColumn('company_profiles', 'owner_id')) {
            return;
        }

        $defaultOwnerId = DB::table('users')->orderBy('id')->value('id');

        if ($defaultOwnerId !== null) {
            DB::table('company_profiles')
                ->whereNull('owner_id')
                ->update(['owner_id' => $defaultOwnerId]);
        }

        Schema::table('company_profiles', function (Blueprint $table) {
            try {
                $table->dropForeign(['owner_id']);
            } catch (Throwable) {
                //
            }

            try {
                $table->dropIndex(['owner_id']);
            } catch (Throwable) {
                //
            }
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->unique('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

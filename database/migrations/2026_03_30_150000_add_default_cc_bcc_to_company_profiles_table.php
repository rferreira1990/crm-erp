<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('mail_default_cc', 150)->nullable();
            $table->string('mail_default_bcc', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'mail_default_cc',
                'mail_default_bcc',
            ]);
        });
    }
};

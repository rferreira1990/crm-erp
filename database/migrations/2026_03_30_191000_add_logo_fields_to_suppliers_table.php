<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('logo_disk', 50)
                ->default('public')
                ->after('preferred_contact_method');
            $table->string('logo_path', 255)
                ->nullable()
                ->after('logo_disk');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'logo_disk',
                'logo_path',
            ]);
        });
    }
};


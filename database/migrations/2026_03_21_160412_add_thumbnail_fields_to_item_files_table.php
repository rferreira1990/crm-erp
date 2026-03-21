<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_files', function (Blueprint $table) {
            $table->string('thumb_disk')->nullable()->after('disk');
            $table->string('thumb_path')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('item_files', function (Blueprint $table) {
            $table->dropColumn(['thumb_disk', 'thumb_path']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Normalizar valores vazios para NULL
        |--------------------------------------------------------------------------
        | Evita que múltiplas strings vazias ("") rebentem com o índice único.
        */
        DB::table('items')
            ->where('barcode', '')
            ->update(['barcode' => null]);

        /*
        |--------------------------------------------------------------------------
        | Verificar duplicados reais antes de criar o índice
        |--------------------------------------------------------------------------
        | Se já existirem duplicados, a migration falha com mensagem clara.
        */
        $duplicates = DB::table('items')
            ->select('barcode', DB::raw('COUNT(*) as total'))
            ->whereNotNull('barcode')
            ->where('barcode', '<>', '')
            ->groupBy('barcode')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('barcode')
            ->toArray();

        if (! empty($duplicates)) {
            throw new RuntimeException(
                'Não foi possível criar o índice único em items.barcode porque existem códigos de barras duplicados: '
                . implode(', ', $duplicates)
            );
        }

        Schema::table('items', function (Blueprint $table) {
            $table->unique('barcode', 'items_barcode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique('items_barcode_unique');
        });
    }
};

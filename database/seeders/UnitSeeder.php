<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'UN', 'name' => 'Unidade', 'factor' => 1],
            ['code' => 'M', 'name' => 'Metro', 'factor' => 1],
            ['code' => 'M2', 'name' => 'Metro quadrado', 'factor' => 1],
            ['code' => 'M3', 'name' => 'Metro cúbico', 'factor' => 1],
            ['code' => 'KG', 'name' => 'Quilograma', 'factor' => 1],
            ['code' => 'H', 'name' => 'Hora', 'factor' => 1],
            ['code' => 'DIA', 'name' => 'Dia', 'factor' => 1],
            ['code' => 'CX', 'name' => 'Caixa', 'factor' => 1],
            ['code' => 'LT', 'name' => 'Litro', 'factor' => 1],
        ];

        foreach ($rows as $row) {
            Unit::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'factor' => $row['factor'],
                    'is_active' => true,
                ]
            );
        }
    }
}

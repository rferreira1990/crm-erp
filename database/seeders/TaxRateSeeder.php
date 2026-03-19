<?php

namespace Database\Seeders;

use App\Models\TaxExemptionReason;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $m10 = TaxExemptionReason::where('code', 'M10')->first();

        $rows = [
            [
                'name' => 'Taxa normal',
                'percent' => 23.00,
                'saft_code' => 'NOR',
                'country_code' => 'PT',
                'is_exempt' => false,
                'is_default' => true,
                'exemption_reason_id' => null,
                'sort_order' => 10,
            ],
            [
                'name' => 'Intermédia',
                'percent' => 13.00,
                'saft_code' => 'INT',
                'country_code' => 'PT',
                'is_exempt' => false,
                'is_default' => false,
                'exemption_reason_id' => null,
                'sort_order' => 20,
            ],
            [
                'name' => 'Reduzida',
                'percent' => 6.00,
                'saft_code' => 'RED',
                'country_code' => 'PT',
                'is_exempt' => false,
                'is_default' => false,
                'exemption_reason_id' => null,
                'sort_order' => 30,
            ],
            [
                'name' => 'Isenta',
                'percent' => 0.00,
                'saft_code' => 'ISE',
                'country_code' => 'PT',
                'is_exempt' => true,
                'is_default' => false,
                'exemption_reason_id' => null,
                'sort_order' => 40,
            ],
            [
                'name' => 'IVA - regime de isenção',
                'percent' => 0.00,
                'saft_code' => 'ISE',
                'country_code' => 'PT',
                'is_exempt' => true,
                'is_default' => false,
                'exemption_reason_id' => $m10?->id,
                'sort_order' => 50,
            ],
            [
                'name' => 'Não Sujeita',
                'percent' => 0.00,
                'saft_code' => 'NS',
                'country_code' => 'PT',
                'is_exempt' => true,
                'is_default' => false,
                'exemption_reason_id' => null,
                'sort_order' => 60,
            ],
            [
                'name' => 'Autoliquidação',
                'percent' => 0.00,
                'saft_code' => 'OUT',
                'country_code' => 'PT',
                'is_exempt' => true,
                'is_default' => false,
                'exemption_reason_id' => null,
                'sort_order' => 70,
            ],
        ];

        foreach ($rows as $row) {
            TaxRate::updateOrCreate(
                [
                    'name' => $row['name'],
                    'saft_code' => $row['saft_code'],
                    'country_code' => $row['country_code'],
                ],
                [
                    'percent' => $row['percent'],
                    'is_exempt' => $row['is_exempt'],
                    'is_default' => $row['is_default'],
                    'is_active' => true,
                    'exemption_reason_id' => $row['exemption_reason_id'],
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}

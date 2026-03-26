<?php

namespace Database\Seeders;

use App\Models\PaymentTerm;
use Illuminate\Database\Seeder;

class PaymentTermSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Pronto pagamento', 'days' => 0, 'sort_order' => 10],
            ['name' => '8 dias', 'days' => 8, 'sort_order' => 20],
            ['name' => '15 dias', 'days' => 15, 'sort_order' => 30],
            ['name' => '30 dias', 'days' => 30, 'sort_order' => 40],
            ['name' => '45 dias', 'days' => 45, 'sort_order' => 50],
            ['name' => '60 dias', 'days' => 60, 'sort_order' => 60],
        ];

        foreach ($defaults as $row) {
            PaymentTerm::firstOrCreate(
                [
                    'owner_id' => null,
                    'name' => $row['name'],
                ],
                [
                    'days' => $row['days'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}

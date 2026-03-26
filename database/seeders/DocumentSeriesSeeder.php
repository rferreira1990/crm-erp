<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentSeries;

class DocumentSeriesSeeder extends Seeder
{
    public function run(): void
    {
        DocumentSeries::create([
            'owner_id' => 1,
            'document_type' => 'budget',
            'prefix' => 'ORC',
            'name' => '2026',
            'year' => 2026,
            'next_number' => 1,
            'is_active' => true,
        ]);
    }
}

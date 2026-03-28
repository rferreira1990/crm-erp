<?php

namespace Database\Seeders;

use App\Models\DocumentSeries;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeriesSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = User::query()->orderBy('id')->value('id');

        if (! $ownerId) {
            return;
        }

        DocumentSeries::firstOrCreate([
            'document_type' => 'budget',
            'name' => (string) now()->year,
        ], [
            'owner_id' => $ownerId,
            'document_type' => 'budget',
            'prefix' => 'ORC',
            'name' => (string) now()->year,
            'year' => (int) now()->year,
            'next_number' => 1,
            'is_active' => true,
        ]);
    }
}

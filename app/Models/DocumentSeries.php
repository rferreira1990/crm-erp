<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSeries extends Model
{
    protected $fillable = [
        'owner_id',
        'document_type',
        'prefix',
        'name',
        'year',
        'next_number',
        'is_active',
    ];

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }
}

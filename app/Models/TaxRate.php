<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'percent',
        'saft_code',
        'country_code',
        'is_exempt',
        'is_default',
        'is_active',
        'exemption_reason_id',
        'sort_order',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
        'is_exempt' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function exemptionReason()
    {
        return $this->belongsTo(TaxExemptionReason::class, 'exemption_reason_id');
    }
}

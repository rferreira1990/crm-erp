<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxExemptionReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'invoice_note',
        'legal_reference',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function taxRates()
    {
        return $this->hasMany(TaxRate::class, 'exemption_reason_id');
    }
}

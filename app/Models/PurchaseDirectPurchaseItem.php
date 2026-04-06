<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseDirectPurchaseItem extends Model
{
    protected $fillable = [
        'owner_id',
        'purchase_direct_purchase_id',
        'item_id',
        'tax_rate_id',
        'description_snapshot',
        'unit_snapshot',
        'quantity',
        'unit_price',
        'vat_percent',
        'line_subtotal',
        'line_vat_amount',
        'line_total',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'vat_percent' => 'decimal:3',
        'line_subtotal' => 'decimal:2',
        'line_vat_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PurchaseDirectPurchase::class, 'purchase_direct_purchase_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }
}


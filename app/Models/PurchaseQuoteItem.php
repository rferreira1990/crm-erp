<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuoteItem extends Model
{
    protected $fillable = [
        'purchase_quote_id',
        'purchase_request_item_id',
        'supplier_item_reference',
        'quoted_qty',
        'unit_price',
        'discount_percent',
        'line_total',
        'lead_time_days',
        'notes',
    ];

    protected $casts = [
        'quoted_qty' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:3',
        'line_total' => 'decimal:2',
        'lead_time_days' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuote::class, 'purchase_quote_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'purchase_request_item_id');
    }
}

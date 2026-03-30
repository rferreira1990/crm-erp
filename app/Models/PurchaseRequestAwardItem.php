<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestAwardItem extends Model
{
    protected $fillable = [
        'award_id',
        'purchase_request_item_id',
        'supplier_id',
        'purchase_quote_id',
        'purchase_quote_item_id',
        'awarded_qty',
        'unit_price',
        'discount_percent',
        'line_total',
        'supplier_item_reference',
        'notes',
        'tie_break_note',
    ];

    protected $casts = [
        'awarded_qty' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:3',
        'line_total' => 'decimal:2',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestAward::class, 'award_id');
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'purchase_request_item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuote::class, 'purchase_quote_id');
    }

    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuoteItem::class, 'purchase_quote_item_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestItem extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'item_id',
        'description',
        'qty',
        'unit_snapshot',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'sort_order' => 'integer',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function quoteItems(): HasMany
    {
        return $this->hasMany(PurchaseQuoteItem::class, 'purchase_request_item_id');
    }
}

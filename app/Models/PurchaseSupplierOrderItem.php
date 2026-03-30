<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseSupplierOrderItem extends Model
{
    protected $fillable = [
        'purchase_supplier_order_id',
        'purchase_request_item_id',
        'purchase_quote_item_id',
        'item_id',
        'description',
        'unit_snapshot',
        'supplier_item_reference',
        'qty',
        'unit_price',
        'discount_percent',
        'line_total',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:3',
        'line_total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrder::class, 'purchase_supplier_order_id');
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'purchase_request_item_id');
    }

    public function quoteItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuoteItem::class, 'purchase_quote_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}


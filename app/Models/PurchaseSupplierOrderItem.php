<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'received_qty',
        'unit_price',
        'discount_percent',
        'line_total',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
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

    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderReceiptItem::class, 'purchase_supplier_order_item_id')
            ->orderByDesc('id');
    }

    public function pendingQty(): float
    {
        $orderedQty = (float) ($this->qty ?? 0);
        $receivedQty = (float) ($this->received_qty ?? 0);

        return round(max(0, $orderedQty - $receivedQty), 3);
    }

    public function isFullyReceived(): bool
    {
        return $this->pendingQty() <= 0.0005;
    }
}

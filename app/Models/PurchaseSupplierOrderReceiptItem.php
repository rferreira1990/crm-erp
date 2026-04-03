<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseSupplierOrderReceiptItem extends Model
{
    protected $fillable = [
        'owner_id',
        'purchase_supplier_order_receipt_id',
        'purchase_supplier_order_item_id',
        'item_id',
        'quantity_received',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrderReceipt::class, 'purchase_supplier_order_receipt_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrderItem::class, 'purchase_supplier_order_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

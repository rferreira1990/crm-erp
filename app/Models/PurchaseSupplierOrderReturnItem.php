<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseSupplierOrderReturnItem extends Model
{
    protected $fillable = [
        'owner_id',
        'purchase_supplier_order_return_id',
        'purchase_supplier_order_item_id',
        'item_id',
        'quantity_returned',
        'reason',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:3',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrderReturn::class, 'purchase_supplier_order_return_id');
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


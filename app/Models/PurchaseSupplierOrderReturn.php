<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseSupplierOrderReturn extends Model
{
    protected $fillable = [
        'owner_id',
        'purchase_supplier_order_id',
        'purchase_supplier_order_receipt_id',
        'return_number',
        'return_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrder::class, 'purchase_supplier_order_id');
    }

    public function linkedReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrderReceipt::class, 'purchase_supplier_order_receipt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderReturnItem::class, 'purchase_supplier_order_return_id')
            ->orderBy('id');
    }

    public function totalReturnedQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseSupplierOrderReturnItem $item) => (float) $item->quantity_returned);
        }

        return (float) $this->items()->sum('quantity_returned');
    }
}


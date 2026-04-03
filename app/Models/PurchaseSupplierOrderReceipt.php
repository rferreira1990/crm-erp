<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseSupplierOrderReceipt extends Model
{
    protected $fillable = [
        'owner_id',
        'purchase_supplier_order_id',
        'receipt_number',
        'receipt_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrder::class, 'purchase_supplier_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderReceiptItem::class, 'purchase_supplier_order_receipt_id')
            ->orderBy('id');
    }

    public function totalReceivedQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseSupplierOrderReceiptItem $item) => (float) $item->quantity_received);
        }

        return (float) $this->items()->sum('quantity_received');
    }
}

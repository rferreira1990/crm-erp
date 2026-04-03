<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseSupplierOrder extends Model
{
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'purchase_request_id',
        'award_id',
        'supplier_id',
        'purchase_quote_id',
        'payment_term_id',
        'currency',
        'status',
        'subtotal_amount',
        'notes',
        'prepared_at',
        'prepared_by',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'prepared_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PREPARED => 'Preparada',
            self::STATUS_PARTIALLY_RECEIVED => 'Rececao parcial',
            self::STATUS_RECEIVED => 'Recebida',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestAward::class, 'award_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuote::class, 'purchase_quote_id');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderReceipt::class)
            ->orderByDesc('receipt_date')
            ->orderByDesc('id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderReturn::class)
            ->orderByDesc('return_date')
            ->orderByDesc('id');
    }

    public function totalOrderedQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseSupplierOrderItem $item) => (float) $item->qty);
        }

        return (float) $this->items()->sum('qty');
    }

    public function totalReceivedQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseSupplierOrderItem $item) => (float) $item->received_qty);
        }

        return (float) $this->items()->sum('received_qty');
    }

    public function totalPendingQty(): float
    {
        $pending = $this->totalOrderedQty() - $this->totalReceivedQty();

        return round(max(0, $pending), 3);
    }

    public function hasPendingReceipt(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->contains(fn (PurchaseSupplierOrderItem $item) => $item->pendingQty() > 0);
        }

        return $this->items()
            ->whereRaw('COALESCE(received_qty, 0) + 0.0005 < qty')
            ->exists();
    }

    public function totalReturnedQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseSupplierOrderItem $item) => (float) $item->returned_qty);
        }

        return (float) $this->items()->sum('returned_qty');
    }

    public function totalNetReceivedQty(): float
    {
        $netReceived = $this->totalReceivedQty() - $this->totalReturnedQty();

        return round(max(0, $netReceived), 3);
    }

    public function hasReturnableQty(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->contains(fn (PurchaseSupplierOrderItem $item) => $item->returnableQty() > 0);
        }

        return $this->items()
            ->whereRaw('COALESCE(received_qty, 0) > COALESCE(returned_qty, 0) + 0.0005')
            ->exists();
    }
}

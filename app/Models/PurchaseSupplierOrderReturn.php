<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseSupplierOrderReturn extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const CONFIRMATION_PENDING = 'pending';
    public const CONFIRMATION_ACCEPTED = 'accepted';
    public const CONFIRMATION_REJECTED = 'rejected';

    protected $fillable = [
        'owner_id',
        'purchase_supplier_order_id',
        'purchase_supplier_order_receipt_id',
        'return_number',
        'return_date',
        'user_id',
        'notes',
        'status',
        'closed_at',
        'closed_by',
        'supplier_confirmation_status',
        'confirmation_at',
        'confirmed_by',
        'confirmation_notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'closed_at' => 'datetime',
        'confirmation_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Aberta',
            self::STATUS_CLOSED => 'Fechada',
        ];
    }

    public static function confirmationStatuses(): array
    {
        return [
            self::CONFIRMATION_PENDING => 'Pendente',
            self::CONFIRMATION_ACCEPTED => 'Aceite',
            self::CONFIRMATION_REJECTED => 'Recusada',
        ];
    }

    public function statusLabel(): string
    {
        $status = (string) ($this->status ?: self::STATUS_OPEN);

        return self::statuses()[$status] ?? $status;
    }

    public function isClosed(): bool
    {
        return (string) $this->status === self::STATUS_CLOSED;
    }

    public function canClose(): bool
    {
        return ! $this->isClosed();
    }

    public function confirmationStatusLabel(): string
    {
        $status = (string) ($this->supplier_confirmation_status ?: self::CONFIRMATION_PENDING);

        return self::confirmationStatuses()[$status] ?? $status;
    }

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

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderReturnItem::class, 'purchase_supplier_order_return_id')
            ->orderBy('id');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderReturnEmailLog::class, 'purchase_supplier_order_return_id')
            ->orderByDesc('sent_at')
            ->orderByDesc('id');
    }

    public function totalReturnedQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseSupplierOrderReturnItem $item) => (float) $item->quantity_returned);
        }

        return (float) $this->items()->sum('quantity_returned');
    }
}

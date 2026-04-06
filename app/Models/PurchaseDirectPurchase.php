<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseDirectPurchase extends Model
{
    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'owner_id',
        'supplier_id',
        'document_number',
        'purchase_date',
        'external_reference',
        'currency',
        'status',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_POSTED => 'Lancada',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseDirectPurchaseItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function totalQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseDirectPurchaseItem $item): float => (float) $item->quantity);
        }

        return (float) $this->items()->sum('quantity');
    }
}


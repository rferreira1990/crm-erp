<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuote extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SELECTED = 'selected';

    protected $fillable = [
        'purchase_request_id',
        'supplier_id',
        'supplier_name_snapshot',
        'lead_time_days',
        'payment_term_snapshot',
        'valid_until',
        'total_amount',
        'currency',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_RECEIVED => 'Recebida',
            self::STATUS_REJECTED => 'Rejeitada',
            self::STATUS_SELECTED => 'Selecionada',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerReceivable extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'owner_id',
        'customer_id',
        'document_number',
        'issue_date',
        'due_date',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'notes',
        'status',
        'user_id',
        'issued_at',
        'issued_by',
        'closed_at',
        'closed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_ISSUED => 'Emitido',
            self::STATUS_CLOSED => 'Fechado',
        ];
    }

    public static function creatableStatuses(): array
    {
        return [
            self::STATUS_DRAFT => self::statuses()[self::STATUS_DRAFT],
            self::STATUS_ISSUED => self::statuses()[self::STATUS_ISSUED],
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function accountEntry(): HasOne
    {
        return $this->hasOne(CustomerAccountEntry::class, 'reference_id')
            ->where('reference_type', self::class)
            ->where('type', CustomerAccountEntry::TYPE_DEBIT);
    }
}

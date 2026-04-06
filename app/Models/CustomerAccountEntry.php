<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAccountEntry extends Model
{
    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'owner_id',
        'customer_id',
        'entry_date',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'user_id',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'reference_id' => 'integer',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_DEBIT => 'Debito',
            self::TYPE_CREDIT => 'Credito',
            self::TYPE_PAYMENT => 'Recebimento',
            self::TYPE_ADJUSTMENT => 'Ajuste',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? (string) $this->type;
    }

    public function isAutomatic(): bool
    {
        return ! empty($this->reference_type) && ! empty($this->reference_id);
    }

    public function isFromCustomerReceivable(): bool
    {
        return $this->reference_type === CustomerReceivable::class
            && (int) ($this->reference_id ?? 0) > 0;
    }

    public function signedAmount(): float
    {
        return $this->isDebitEffect()
            ? (float) $this->amount
            : (float) $this->amount * -1;
    }

    public function isDebitEffect(): bool
    {
        return in_array($this->type, [self::TYPE_DEBIT, self::TYPE_ADJUSTMENT], true);
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

    public function scopeForOwner(Builder $query, int $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
    }
}

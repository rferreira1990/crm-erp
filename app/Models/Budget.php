<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'designation',
        'customer_id',
        'status',
        'budget_date',
        'zone',
        'project_name',
        'notes',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'budget_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CREATED = 'created';
    public const STATUS_SENT = 'sent';
    public const STATUS_WAITING_RESPONSE = 'waiting_response';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected static function booted(): void
    {
        static::creating(function (Budget $budget) {
            if (empty($budget->code)) {
                $budget->code = static::generateCode();
            }

            if (empty($budget->status)) {
                $budget->status = self::STATUS_DRAFT;
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_CREATED,
            self::STATUS_SENT,
            self::STATUS_WAITING_RESPONSE,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ];
    }

    public static function generateCode(): string
    {
        do {
            $code = 'ORC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
        return $this->hasMany(BudgetItem::class);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isDeletable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_CREATED => 'Criado',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_WAITING_RESPONSE => 'Aguarda resposta',
            self::STATUS_ACCEPTED => 'Aceite',
            self::STATUS_REJECTED => 'Não aceite',
            default => ucfirst((string) $this->status),
        };
    }

    public function allowedNextStatuses(): array
    {
        return match ($this->status) {
            self::STATUS_DRAFT => [
                self::STATUS_CREATED,
            ],
            self::STATUS_CREATED => [
                self::STATUS_SENT,
                self::STATUS_WAITING_RESPONSE,
            ],
            self::STATUS_SENT => [
                self::STATUS_WAITING_RESPONSE,
                self::STATUS_ACCEPTED,
                self::STATUS_REJECTED,
            ],
            self::STATUS_WAITING_RESPONSE => [
                self::STATUS_ACCEPTED,
                self::STATUS_REJECTED,
            ],
            default => [],
        };
    }

    public function canChangeToStatus(string $newStatus): bool
    {
        return in_array($newStatus, $this->allowedNextStatuses(), true);
    }
}

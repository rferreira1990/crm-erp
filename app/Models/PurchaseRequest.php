<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'owner_id',
        'code',
        'title',
        'work_id',
        'needed_at',
        'deadline_at',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'needed_at' => 'date',
        'deadline_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseRequest $purchaseRequest) {
            if (empty($purchaseRequest->code)) {
                $purchaseRequest->code = self::generateNextCode();
            }

            if (empty($purchaseRequest->status)) {
                $purchaseRequest->status = self::STATUS_DRAFT;
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_CLOSED => 'Fechado',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
        ], true);
    }

    public function canChangeTo(string $newStatus): bool
    {
        $transitions = [
            self::STATUS_DRAFT => [self::STATUS_SENT, self::STATUS_CANCELLED],
            self::STATUS_SENT => [self::STATUS_CLOSED, self::STATUS_CANCELLED, self::STATUS_DRAFT],
            self::STATUS_CLOSED => [],
            self::STATUS_CANCELLED => [],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? [], true);
    }

    public static function generateNextCode(): string
    {
        $last = self::withTrashed()
            ->where('code', 'like', 'RFQ-%')
            ->orderByDesc('id')
            ->first();

        if (! $last || ! $last->code) {
            return 'RFQ-000001';
        }

        $lastNumber = (int) str_replace('RFQ-', '', $last->code);
        $nextNumber = $lastNumber + 1;

        return 'RFQ-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
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
        return $this->hasMany(PurchaseRequestItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(PurchaseQuote::class)
            ->orderBy('total_amount')
            ->orderBy('lead_time_days');
    }

    public function selectedQuote(): HasOne
    {
        return $this->hasOne(PurchaseQuote::class)
            ->where('status', PurchaseQuote::STATUS_SELECTED);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($search) {
            $subQuery
                ->where('code', 'like', '%' . $search . '%')
                ->orWhere('title', 'like', '%' . $search . '%')
                ->orWhere('notes', 'like', '%' . $search . '%')
                ->orWhereHas('work', function (Builder $workQuery) use ($search) {
                    $workQuery
                        ->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
        });
    }
}

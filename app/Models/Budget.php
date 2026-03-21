<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Budget extends Model
{
    protected $fillable = [
        'code',
        'customer_id',
        'status',
        'notes',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

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
        return $this->hasMany(BudgetItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Indica se o orçamento ainda pode ser editado.
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    protected static function booted(): void
    {
        static::creating(function (Budget $budget) {
            if (empty($budget->code)) {
                $budget->code = 'TMP-' . Str::upper(Str::uuid()->toString());
            }

            if (Auth::check()) {
                $budget->created_by = Auth::id();
                $budget->updated_by = Auth::id();
            }
        });

        static::created(function (Budget $budget) {
            $finalCode = self::generateCodeFromId($budget->id);

            if ($budget->code !== $finalCode) {
                $budget->code = $finalCode;
                $budget->saveQuietly();
            }
        });

        static::updating(function (Budget $budget) {
            if (Auth::check()) {
                $budget->updated_by = Auth::id();
            }
        });
    }

    public static function generateCodeFromId(int $id): string
    {
        return 'ORC-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestAward extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REPLACED = 'replaced';

    public const MODE_LOWEST_TOTAL = 'lowest_total';
    public const MODE_LOWEST_PER_LINE = 'lowest_per_line';
    public const MODE_FORCED_SUPPLIER = 'forced_supplier';

    protected $fillable = [
        'purchase_request_id',
        'mode',
        'forced_supplier_id',
        'selected_quote_id',
        'justification',
        'allow_partial',
        'status',
        'decision_payload',
        'generated_orders_count',
        'generated_items_count',
        'decided_at',
        'decided_by',
        'replaced_by_award_id',
    ];

    protected $casts = [
        'allow_partial' => 'boolean',
        'decision_payload' => 'array',
        'generated_orders_count' => 'integer',
        'generated_items_count' => 'integer',
        'decided_at' => 'datetime',
    ];

    public static function modes(): array
    {
        return [
            self::MODE_LOWEST_TOTAL => 'Menor total global',
            self::MODE_LOWEST_PER_LINE => 'Menor preco por linha',
            self::MODE_FORCED_SUPPLIER => 'Fornecedor forcado',
        ];
    }

    public function modeLabel(): string
    {
        return self::modes()[$this->mode] ?? $this->mode;
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function forcedSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'forced_supplier_id');
    }

    public function selectedQuote(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuote::class, 'selected_quote_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function replacedByAward(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_award_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestAwardItem::class, 'award_id')
            ->orderBy('purchase_request_item_id');
    }

    public function preparedOrders(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrder::class, 'award_id')
            ->orderBy('supplier_id');
    }
}


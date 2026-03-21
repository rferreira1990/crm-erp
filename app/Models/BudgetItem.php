<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItem extends Model
{
    protected $fillable = [
        'budget_id',
        'item_id',
        'sort_order',
        'item_code',
        'item_name',
        'item_type',
        'description',
        'unit_name',
        'tax_rate_id',
        'tax_rate_name',
        'tax_percent',
        'quantity',
        'unit_price',
        'discount_percent',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
    ];

    protected $casts = [
        'tax_percent' => 'decimal:2',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Orçamento a que pertence a linha.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Artigo original associado, quando existir.
     * Mantido apenas como referência; os valores do orçamento
     * vivem no snapshot desta linha.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Taxa de IVA original associada.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}

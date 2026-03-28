<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_WORK_MATERIAL = 'work_material';
    public const TYPE_MANUAL_ENTRY = 'manual_entry';
    public const TYPE_MANUAL_EXIT = 'manual_exit';
    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';
    public const DIRECTION_ADJUSTMENT = 'adjustment';

    public const MANUAL_REASON_STOCK_COUNT = 'stock_count';
    public const MANUAL_REASON_DAMAGED = 'damaged';
    public const MANUAL_REASON_LOST = 'lost';
    public const MANUAL_REASON_FOUND = 'found';
    public const MANUAL_REASON_INTERNAL_USE = 'internal_use';
    public const MANUAL_REASON_RETURN_TO_SUPPLIER = 'return_to_supplier';
    public const MANUAL_REASON_SUPPLIER_RETURN = 'supplier_return';
    public const MANUAL_REASON_CORRECTION = 'correction';
    public const MANUAL_REASON_OTHER = 'other';

    protected $fillable = [
        'item_id',
        'work_material_id',
        'movement_type',
        'direction',
        'quantity',
        'stock_before',
        'stock_after',
        'occurred_at',
        'source_type',
        'source_id',
        'manual_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
        'occurred_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function workMaterial(): BelongsTo
    {
        return $this->belongsTo(WorkMaterial::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, string>
     */
    public static function manualReasons(): array
    {
        return [
            self::MANUAL_REASON_STOCK_COUNT => 'Contagem de stock',
            self::MANUAL_REASON_DAMAGED => 'Material danificado',
            self::MANUAL_REASON_LOST => 'Material perdido',
            self::MANUAL_REASON_FOUND => 'Material encontrado',
            self::MANUAL_REASON_INTERNAL_USE => 'Consumo interno',
            self::MANUAL_REASON_RETURN_TO_SUPPLIER => 'Devolucao a fornecedor',
            self::MANUAL_REASON_SUPPLIER_RETURN => 'Retorno de fornecedor',
            self::MANUAL_REASON_CORRECTION => 'Correcao administrativa',
            self::MANUAL_REASON_OTHER => 'Outro motivo',
        ];
    }
}

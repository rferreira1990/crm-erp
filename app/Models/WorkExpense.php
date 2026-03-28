<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkExpense extends Model
{
    public const TYPE_MEAL = 'meal';
    public const TYPE_FUEL = 'fuel';
    public const TYPE_TRAVEL_KM = 'travel_km';
    public const TYPE_TOLL = 'toll';
    public const TYPE_PARKING = 'parking';
    public const TYPE_ACCOMMODATION = 'accommodation';
    public const TYPE_MACHINE_RENTAL = 'machine_rental';
    public const TYPE_SUBCONTRACT = 'subcontract';
    public const TYPE_CONSUMABLE = 'consumable';
    public const TYPE_TRANSPORT = 'transport';
    public const TYPE_LICENSE_FEE = 'license_fee';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'work_id',
        'type',
        'expense_date',
        'description',
        'user_id',
        'supplier_name',
        'receipt_number',
        'qty',
        'unit_cost',
        'total_cost',
        'km',
        'from_location',
        'to_location',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'qty' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'km' => 'decimal:3',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_MEAL => 'Refeicao',
            self::TYPE_FUEL => 'Combustivel',
            self::TYPE_TRAVEL_KM => 'Deslocacao km',
            self::TYPE_TOLL => 'Portagem',
            self::TYPE_PARKING => 'Estacionamento',
            self::TYPE_ACCOMMODATION => 'Alojamento',
            self::TYPE_MACHINE_RENTAL => 'Aluguer equipamento',
            self::TYPE_SUBCONTRACT => 'Subempreitada',
            self::TYPE_CONSUMABLE => 'Consumivel',
            self::TYPE_TRANSPORT => 'Transporte',
            self::TYPE_LICENSE_FEE => 'Licenca/taxa',
            self::TYPE_OTHER => 'Outro',
        ];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Budget extends Model
{
    use HasFactory;

    /**
     * Campos que podem ser preenchidos em massa
     */
    protected $fillable = [
        'code',
        'customer_id',
        'status',
        'total',
        'notes',
        'created_by',
    ];

    /**
     * Casts para garantir tipos corretos
     */
    protected $casts = [
        'total' => 'decimal:2',
    ];

    /**
     * Relação com cliente
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Quem criou o orçamento
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Boot do model
     * - gera código automático
     */
    protected static function booted()
    {
        static::creating(function ($budget) {

            // evita sobrescrever se já existir (segurança)
            if ($budget->code) {
                return;
            }

            // último ID
            $last = self::latest('id')->first();
            $nextNumber = $last ? $last->id + 1 : 1;

            // formato: ORC-000001
            $budget->code = 'ORC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}

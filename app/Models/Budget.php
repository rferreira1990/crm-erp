<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'code',
        'customer_id',
        'status',
        'total',
        'notes',
        'created_by',
    ];

    // relações
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // gerar código automático
    protected static function booted()
    {
        static::creating(function ($budget) {
            $last = self::latest('id')->first();
            $number = $last ? $last->id + 1 : 1;

            $budget->code = 'ORC-' . str_pad($number, 6, '0', STR_PAD_LEFT);
        });
    }
}


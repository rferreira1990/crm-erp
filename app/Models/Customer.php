<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campos permitidos para atribuição em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'nif',
        'email',
        'phone',
        'mobile',
        'contact_person',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'country',
        'default_discount',
        'payment_terms_days',
        'source',
        'status',
        'last_contact_at',
        'notes',
        'is_active',
        'created_by',
    ];

    /**
     * Conversões automáticas de tipos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'default_discount' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'last_contact_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Utilizador que criou o cliente.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

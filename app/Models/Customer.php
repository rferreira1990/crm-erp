<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Budget;


class Customer extends Model
{
    use SoftDeletes;

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

    protected $casts = [
        'last_contact_at' => 'datetime',
        'default_discount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->code)) {
                $customer->code = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $lastCustomer = self::withTrashed()
            ->whereNotNull('code')
            ->where('code', 'like', 'CLI-%')
            ->orderByDesc('id')
            ->first();

        if (!$lastCustomer || !$lastCustomer->code) {
            return 'CLI-000001';
        }

        $lastNumber = (int) str_replace('CLI-', '', $lastCustomer->code);
        $nextNumber = $lastNumber + 1;

        return 'CLI-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }
}

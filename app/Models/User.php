<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class, 'owner_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'owner_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'owner_id');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'owner_id');
    }

    public function itemFamilies(): HasMany
    {
        return $this->hasMany(ItemFamily::class, 'owner_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'owner_id');
    }
}

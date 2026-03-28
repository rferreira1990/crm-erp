<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'job_title',
        'hourly_cost',
        'hourly_sale_price',
        'is_labor_enabled',
        'is_active',
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
            'hourly_cost' => 'decimal:2',
            'hourly_sale_price' => 'decimal:2',
            'is_labor_enabled' => 'boolean',
            'is_active' => 'boolean',
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

    public function managedWorks(): HasMany
    {
        return $this->hasMany(Work::class, 'technical_manager_id');
    }

    public function works(): BelongsToMany
    {
        return $this->belongsToMany(Work::class, 'work_user')
            ->withTimestamps();
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(WorkTaskAssignment::class);
    }

    public function workExpenses(): HasMany
    {
        return $this->hasMany(WorkExpense::class);
    }

    public function scopeAssignableToWorks(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $subQuery) {
                $subQuery
                    ->permission([
                        'works.view',
                        'works.create',
                        'works.update',
                    ])
                    ->orWhereHas('roles', function (Builder $roleQuery) {
                        $roleQuery->whereIn('name', ['admin']);
                    });
            })
            ->orderBy('name');
    }

    public function scopeAssignableToWorkLabor(Builder $query): Builder
    {
        return $query
            ->assignableToWorks()
            ->where('is_labor_enabled', true);
    }
}

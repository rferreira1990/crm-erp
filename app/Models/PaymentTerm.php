<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function scopeVisibleForOwner(Builder $query, ?int $ownerId = null): Builder
    {
        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function displayLabel(): string
    {
        if ($this->days !== null) {
            return $this->name . ' (' . $this->days . ' dias)';
        }

        return $this->name;
    }
}

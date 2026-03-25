<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    use BelongsToOwner;

    protected $fillable = [
        'owner_id',
        'code',
        'name',
        'factor',
        'is_active',
    ];

    protected $casts = [
        'factor' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    protected static function ownerScopeAllowsSharedRecords(): bool
    {
        return true;
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}

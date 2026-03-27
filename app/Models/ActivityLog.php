<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'owner_id',
        'user_id',
        'action',
        'entity',
        'entity_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForOwner(Builder $query, int $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeForEntity(Builder $query, string $entity, ?int $entityId = null): Builder
    {
        $query->where('entity', $entity);

        if ($entityId !== null) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }
}

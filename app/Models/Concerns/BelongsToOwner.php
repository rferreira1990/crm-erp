<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToOwner
{
    protected static function bootBelongsToOwner(): void
    {
        static::addGlobalScope('owner', function (Builder $query) {
            if (! Auth::check()) {
                return;
            }

            $table = $query->getModel()->getTable();
            $ownerId = Auth::id();

            if (static::ownerScopeAllowsSharedRecords()) {
                $query->where(function (Builder $subQuery) use ($table, $ownerId) {
                    $subQuery
                        ->where("{$table}.owner_id", $ownerId)
                        ->orWhereNull("{$table}.owner_id");
                });

                return;
            }

            $query->where("{$table}.owner_id", $ownerId);
        });

        static::creating(function ($model) {
            if (empty($model->owner_id) && Auth::check()) {
                $model->owner_id = Auth::id();
            }
        });
    }

    protected static function ownerScopeAllowsSharedRecords(): bool
    {
        return false;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

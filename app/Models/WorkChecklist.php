<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkChecklist extends Model
{
    protected $fillable = [
        'owner_id',
        'work_id',
        'name',
        'description',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkChecklistItem::class, 'work_checklist_id')
            ->orderBy('id');
    }

    public function completedItemsCount(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->where('is_completed', true)->count();
        }

        return (int) $this->items()->where('is_completed', true)->count();
    }

    public function totalItemsCount(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->count();
        }

        return (int) $this->items()->count();
    }

    public function pendingRequiredItemsCount(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items
                ->where('is_required', true)
                ->where('is_completed', false)
                ->count();
        }

        return (int) $this->items()
            ->where('is_required', true)
            ->where('is_completed', false)
            ->count();
    }
}


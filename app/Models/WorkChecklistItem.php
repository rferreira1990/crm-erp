<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkChecklistItem extends Model
{
    protected $fillable = [
        'owner_id',
        'work_checklist_id',
        'description',
        'is_required',
        'is_completed',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(WorkChecklist::class, 'work_checklist_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}


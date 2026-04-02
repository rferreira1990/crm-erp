<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkChecklistTemplateItem extends Model
{
    protected $fillable = [
        'owner_id',
        'work_checklist_template_id',
        'description',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkChecklistTemplate::class, 'work_checklist_template_id');
    }
}

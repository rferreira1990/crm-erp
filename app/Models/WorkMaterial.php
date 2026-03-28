<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkMaterial extends Model
{
    protected $fillable = [
        'work_id',
        'item_id',
        'description_snapshot',
        'unit_snapshot',
        'qty',
        'unit_cost',
        'total_cost',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

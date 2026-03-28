<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskAssignment extends Model
{
    protected $fillable = [
        'work_task_id',
        'user_id',
        'role_snapshot',
        'hourly_cost_snapshot',
        'hourly_sale_price_snapshot',
        'start_time',
        'end_time',
        'worked_minutes',
        'labor_cost_total',
        'labor_sale_total',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'hourly_cost_snapshot' => 'decimal:2',
        'hourly_sale_price_snapshot' => 'decimal:2',
        'worked_minutes' => 'integer',
        'labor_cost_total' => 'decimal:2',
        'labor_sale_total' => 'decimal:2',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function workedHours(): float
    {
        return round(((int) $this->worked_minutes) / 60, 2);
    }
}

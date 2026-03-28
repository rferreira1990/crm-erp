<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkTask extends Model
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'work_id',
        'title',
        'description',
        'status',
        'assigned_user_id',
        'planned_date',
        'planned_start_time',
        'planned_end_time',
        'completed_at',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PLANNED => 'Planeada',
            self::STATUS_IN_PROGRESS => 'Em curso',
            self::STATUS_COMPLETED => 'Concluida',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkTaskAssignment::class, 'work_task_id')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function laborCostTotal(): float
    {
        if ($this->relationLoaded('assignments')) {
            return (float) $this->assignments->sum('labor_cost_total');
        }

        return (float) $this->assignments()->sum('labor_cost_total');
    }

    public function laborMinutesTotal(): int
    {
        if ($this->relationLoaded('assignments')) {
            return (int) $this->assignments->sum('worked_minutes');
        }

        return (int) $this->assignments()->sum('worked_minutes');
    }
}

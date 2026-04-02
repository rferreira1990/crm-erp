<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Work extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'owner_id',
        'customer_id',
        'budget_id',
        'code',
        'name',
        'status',
        'work_type',
        'location',
        'postal_code',
        'city',
        'start_date_planned',
        'end_date_planned',
        'start_date_actual',
        'end_date_actual',
        'technical_manager_id',
        'description',
        'internal_notes',
        'other_costs',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date_planned' => 'date',
        'end_date_planned' => 'date',
        'start_date_actual' => 'date',
        'end_date_actual' => 'date',
        'other_costs' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PLANNED => 'Planeada',
            self::STATUS_IN_PROGRESS => 'Em curso',
            self::STATUS_SUSPENDED => 'Suspensa',
            self::STATUS_COMPLETED => 'Concluida',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function technicalManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_manager_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function team(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_user')
            ->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(WorkStatusHistory::class)
            ->latest('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkTask::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function taskAssignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            WorkTaskAssignment::class,
            WorkTask::class,
            'work_id',
            'work_task_id',
            'id',
            'id'
        );
    }

    public function materials(): HasMany
    {
        return $this->hasMany(WorkMaterial::class)
            ->orderByDesc('id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(WorkExpense::class)
            ->orderByDesc('expense_date')
            ->orderByDesc('id');
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(WorkDailyReport::class)
            ->orderByDesc('report_date')
            ->orderByDesc('id');
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class)
            ->orderByDesc('id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PLANNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_SUSPENDED,
        ], true);
    }

    public function canBeDeleted(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    public function canChangeTo(string $newStatus): bool
    {
        $transitions = [
            self::STATUS_PLANNED => [
                self::STATUS_IN_PROGRESS,
                self::STATUS_SUSPENDED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_IN_PROGRESS => [
                self::STATUS_SUSPENDED,
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_SUSPENDED => [
                self::STATUS_IN_PROGRESS,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? [], true);
    }

    public static function generateCode(int $sequence): string
    {
        return 'OBR-' . now()->format('Y') . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function plannedRevenue(): float
    {
        return (float) ($this->budget?->total ?? 0);
    }

    public function materialsCost(): float
    {
        if ($this->relationLoaded('materials')) {
            return (float) $this->materials->sum('total_cost');
        }

        return (float) $this->materials()->sum('total_cost');
    }

    public function laborCost(): float
    {
        if ($this->relationLoaded('tasks')) {
            return (float) $this->tasks->sum(function (WorkTask $task) {
                return $task->laborCostTotal();
            });
        }

        return (float) $this->taskAssignments()->sum('labor_cost_total');
    }

    public function manualOtherCosts(): float
    {
        return (float) ($this->other_costs ?? 0);
    }

    public function expensesCost(): float
    {
        if ($this->relationLoaded('expenses')) {
            return (float) $this->expenses->sum('total_cost');
        }

        return (float) $this->expenses()->sum('total_cost');
    }

    public function otherCosts(): float
    {
        return $this->manualOtherCosts() + $this->expensesCost();
    }

    public function totalCosts(): float
    {
        return $this->materialsCost() + $this->laborCost() + $this->otherCosts();
    }

    public function estimatedGrossMargin(): float
    {
        return $this->plannedRevenue() - $this->totalCosts();
    }
}

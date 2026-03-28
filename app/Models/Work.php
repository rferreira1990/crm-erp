<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date_planned' => 'date',
        'end_date_planned' => 'date',
        'start_date_actual' => 'date',
        'end_date_actual' => 'date',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PLANNED => 'Planeada',
            self::STATUS_IN_PROGRESS => 'Em curso',
            self::STATUS_SUSPENDED => 'Suspensa',
            self::STATUS_COMPLETED => 'Concluída',
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
}

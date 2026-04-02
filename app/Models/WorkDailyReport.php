<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkDailyReport extends Model
{
    public const STATUS_NORMAL = 'normal';
    public const STATUS_CONDITIONED = 'conditioned';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'owner_id',
        'work_id',
        'user_id',
        'report_date',
        'day_status',
        'work_summary',
        'hours_spent',
        'notes',
        'incidents',
    ];

    protected $casts = [
        'report_date' => 'date',
        'hours_spent' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NORMAL => 'Normal',
            self::STATUS_CONDITIONED => 'Condicionado',
            self::STATUS_STOPPED => 'Parado',
            self::STATUS_PARTIAL => 'Concluido parcialmente',
        ];
    }

    public function getDayStatusLabelAttribute(): string
    {
        return self::statuses()[$this->day_status] ?? $this->day_status;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkDailyReportItem::class)
            ->orderBy('id');
    }
}


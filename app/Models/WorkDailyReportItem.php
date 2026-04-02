<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkDailyReportItem extends Model
{
    protected $fillable = [
        'owner_id',
        'work_daily_report_id',
        'item_id',
        'description_snapshot',
        'quantity',
        'unit_cost_snapshot',
        'total_cost_snapshot',
        'unit_snapshot',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost_snapshot' => 'decimal:2',
        'total_cost_snapshot' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(WorkDailyReport::class, 'work_daily_report_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}

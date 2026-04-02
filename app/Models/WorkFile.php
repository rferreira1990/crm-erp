<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkFile extends Model
{
    public const CATEGORY_PHOTO = 'photo';
    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_CERTIFICATE = 'certificate';
    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'owner_id',
        'work_id',
        'work_daily_report_id',
        'user_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'category',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public static function categories(): array
    {
        return [
            self::CATEGORY_PHOTO => 'Foto',
            self::CATEGORY_DOCUMENT => 'Documento',
            self::CATEGORY_CERTIFICATE => 'Certificado',
            self::CATEGORY_OTHER => 'Outro',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(WorkDailyReport::class, 'work_daily_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReadableSizeAttribute(): string
    {
        $size = (int) $this->file_size;

        if ($size < 1024) {
            return $size . ' B';
        }

        if ($size < 1024 * 1024) {
            return number_format($size / 1024, 2, ',', '.') . ' KB';
        }

        return number_format($size / (1024 * 1024), 2, ',', '.') . ' MB';
    }
}


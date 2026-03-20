<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ItemFile extends Model
{
    protected $fillable = [
        'item_id',
        'disk',
        'file_path',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'type',
        'is_primary',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->file_path);
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

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isPdf(): bool
    {
        return $this->type === 'pdf';
    }
}

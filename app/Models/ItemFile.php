<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemFile extends Model
{
    protected $fillable = [
        'item_id',
        'disk',
        'thumb_disk',
        'file_path',
        'thumb_path',
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
        return route('items.files.show', [$this->item_id, $this->id]);
    }

    public function getThumbUrlAttribute(): string
    {
        if ($this->isImage() && ! empty($this->thumb_path)) {
            return route('items.files.show', [$this->item_id, $this->id]) . '?variant=thumb';
        }

        return $this->url;
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

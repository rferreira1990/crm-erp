<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierFile extends Model
{
    protected $fillable = [
        'supplier_id',
        'disk',
        'file_path',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getUrlAttribute(): string
    {
        return route('suppliers.files.show', [$this->supplier_id, $this->id]);
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


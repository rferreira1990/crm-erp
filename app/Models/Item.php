<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'type',
        'description',
        'family_id',
        'brand_id',
        'unit_id',
        'tax_rate_id',
        'barcode',
        'supplier_reference',
        'cost_price',
        'sale_price',
        'max_discount_percent',
        'tracks_stock',
        'min_stock',
        'max_stock',
        'stock_alert',
        'image_path',
        'website_short_description',
        'website_long_description',
        'online_weight',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'max_discount_percent' => 'decimal:2',
        'min_stock' => 'decimal:3',
        'max_stock' => 'decimal:3',
        'online_weight' => 'decimal:3',
        'tracks_stock' => 'boolean',
        'stock_alert' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Item $item) {
            if (empty($item->code)) {
                $nextId = (static::withTrashed()->max('id') ?? 0) + 1;
                $item->code = 'ART-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            }

            if (Auth::check()) {
                $item->created_by = Auth::id();
                $item->updated_by = Auth::id();
            }

            if ($item->type === 'service') {
                $item->tracks_stock = false;
                $item->min_stock = 0;
                $item->max_stock = null;
                $item->stock_alert = false;
            }
        });

        static::updating(function (Item $item) {
            if (Auth::check()) {
                $item->updated_by = Auth::id();
            }

            if ($item->type === 'service') {
                $item->tracks_stock = false;
                $item->min_stock = 0;
                $item->max_stock = null;
                $item->stock_alert = false;
            }
        });
    }

    public function family()
    {
        return $this->belongsTo(ItemFamily::class, 'family_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
        public function files(): HasMany
    {
        return $this->hasMany(ItemFile::class)->orderBy('sort_order')->orderByDesc('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemFile::class)
            ->where('type', 'image')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ItemFile::class)
            ->where('type', 'pdf')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ItemFile::class)
            ->where('type', 'image')
            ->where('is_primary', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'tracks_stock' => 'boolean',
        'min_stock' => 'decimal:3',
        'max_stock' => 'decimal:3',
        'stock_alert' => 'boolean',
        'online_weight' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Item $item) {
            if (empty($item->code)) {
                $item->code = self::generateNextCode();
            }

            if ($item->type === 'service') {
                $item->tracks_stock = false;
                $item->min_stock = 0;
                $item->max_stock = null;
                $item->stock_alert = false;
            }
        });

        static::updating(function (Item $item) {
            if ($item->type === 'service') {
                $item->tracks_stock = false;
                $item->min_stock = 0;
                $item->max_stock = null;
                $item->stock_alert = false;
            }
        });
    }

    public static function generateNextCode(): string
    {
        $lastItem = self::withTrashed()
            ->whereNotNull('code')
            ->where('code', 'like', 'ART-%')
            ->orderByDesc('id')
            ->first();

        if (!$lastItem || !$lastItem->code) {
            return 'ART-000001';
        }

        $lastNumber = (int) str_replace('ART-', '', $lastItem->code);
        $nextNumber = $lastNumber + 1;

        return 'ART-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function family()
    {
        return $this->belongsTo(ItemFamily::class, 'family_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

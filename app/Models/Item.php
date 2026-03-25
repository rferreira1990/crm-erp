<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToOwner;

    protected $fillable = [
        'owner_id',
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
            /*
            |--------------------------------------------------------------------------
            | Código temporário (seguro em concorrência)
            |--------------------------------------------------------------------------
            */
            if (empty($item->code)) {
                $item->code = 'TMP-' . Str::upper(Str::uuid()->toString());
            }

            /*
            |--------------------------------------------------------------------------
            | Auditoria
            |--------------------------------------------------------------------------
            */
            if (Auth::check()) {
                $item->created_by = Auth::id();
                $item->updated_by = Auth::id();
            }

            /*
            |--------------------------------------------------------------------------
            | Regras para serviços
            |--------------------------------------------------------------------------
            */
            if ($item->type === 'service') {
                $item->tracks_stock = false;
                $item->min_stock = 0;
                $item->max_stock = null;
                $item->stock_alert = false;
            }
        });

        static::created(function (Item $item) {
            /*
            |--------------------------------------------------------------------------
            | Código final baseado no ID real
            |--------------------------------------------------------------------------
            */
            $finalCode = self::generateCodeFromId($item->id);

            if ($item->code !== $finalCode) {
                $item->code = $finalCode;
                $item->saveQuietly();
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

    /*
    |--------------------------------------------------------------------------
    | Helper para gerar código
    |--------------------------------------------------------------------------
    */
    public static function generateCodeFromId(int $id): string
    {
        return 'ART-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Relações
    |--------------------------------------------------------------------------
    */

    public function family(): BelongsTo
    {
        return $this->belongsTo(ItemFamily::class, 'family_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ItemFile::class)
            ->orderBy('sort_order')
            ->orderByDesc('id');
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

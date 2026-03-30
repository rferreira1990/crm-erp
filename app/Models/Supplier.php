<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'code',
        'name',
        'tax_number',
        'email',
        'phone',
        'mobile',
        'contact_person',
        'website',
        'external_reference',
        'address',
        'postal_code',
        'city',
        'country',
        'payment_term_id',
        'default_tax_rate_id',
        'default_discount_percent',
        'lead_time_days',
        'minimum_order_value',
        'free_shipping_threshold',
        'preferred_payment_method',
        'default_notes_for_purchases',
        'delivery_instructions',
        'habitual_order_email',
        'preferred_contact_method',
        'logo_disk',
        'logo_path',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_discount_percent' => 'decimal:2',
        'lead_time_days' => 'integer',
        'minimum_order_value' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier) {
            if (empty($supplier->code)) {
                $supplier->code = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $lastSupplier = self::withTrashed()
            ->whereNotNull('code')
            ->where('code', 'like', 'FOR-%')
            ->orderByDesc('id')
            ->first();

        if (! $lastSupplier || ! $lastSupplier->code) {
            return 'FOR-000001';
        }

        $lastNumber = (int) str_replace('FOR-', '', $lastSupplier->code);
        $nextNumber = $lastNumber + 1;

        return 'FOR-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function defaultTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'default_tax_rate_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class)
            ->orderByDesc('is_primary')
            ->orderBy('name');
    }

    public function activeContacts(): HasMany
    {
        return $this->contacts()->where('is_active', true);
    }

    public function purchaseQuotes(): HasMany
    {
        return $this->hasMany(PurchaseQuote::class);
    }

    public function itemReferences(): HasMany
    {
        return $this->hasMany(SupplierItemReference::class)
            ->orderBy('item_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(SupplierFile::class)
            ->latest('id');
    }

    public function catalogFiles(): HasMany
    {
        return $this->hasMany(SupplierFile::class)
            ->where('type', 'catalog')
            ->latest('id');
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(SupplierContact::class)->where('is_primary', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($search) {
            $subQuery->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')
                ->orWhere('tax_number', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('mobile', 'like', '%' . $search . '%')
                ->orWhere('contact_person', 'like', '%' . $search . '%')
                ->orWhereHas('contacts', function (Builder $contactQuery) use ($search) {
                    $contactQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%');
                });
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        return Storage::disk($this->logo_disk ?: 'public')->url($this->logo_path);
    }
}

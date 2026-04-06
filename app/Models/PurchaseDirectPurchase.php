<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseDirectPurchase extends Model
{
    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'owner_id',
        'supplier_id',
        'document_number',
        'purchase_date',
        'due_date',
        'external_reference',
        'currency',
        'payment_method',
        'status',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'invoice_pdf_original_name',
        'invoice_pdf_file_name',
        'invoice_pdf_path',
        'invoice_pdf_mime_type',
        'invoice_pdf_size',
        'invoice_pdf_uploaded_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_pdf_size' => 'integer',
        'invoice_pdf_uploaded_at' => 'datetime',
    ];

    public static function paymentMethods(): array
    {
        return [
            'cash' => 'Numerario',
            'bank_transfer' => 'Transferencia bancaria',
            'multibanco' => 'Multibanco',
            'mbway' => 'MB WAY',
            'card' => 'Cartao',
            'direct_debit' => 'Debito direto',
            'check' => 'Cheque',
            'other' => 'Outro',
        ];
    }

    public function paymentMethodLabel(): string
    {
        return self::paymentMethods()[$this->payment_method] ?? (string) $this->payment_method;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_POSTED => 'Lancada',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseDirectPurchaseItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function supplierAccountEntry(): HasOne
    {
        return $this->hasOne(SupplierAccountEntry::class, 'reference_id')
            ->where('reference_type', self::class)
            ->where('type', SupplierAccountEntry::TYPE_PURCHASE_INVOICE);
    }

    public function totalQty(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(fn (PurchaseDirectPurchaseItem $item): float => (float) $item->quantity);
        }

        return (float) $this->items()->sum('quantity');
    }
}

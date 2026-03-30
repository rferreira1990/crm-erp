<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PurchaseQuote extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SELECTED = 'selected';

    protected $fillable = [
        'purchase_request_id',
        'supplier_id',
        'supplier_name_snapshot',
        'supplier_quote_reference',
        'lead_time_days',
        'payment_term_snapshot',
        'payment_term_id',
        'valid_until',
        'total_amount',
        'currency',
        'status',
        'notes',
        'quote_pdf_disk',
        'quote_pdf_path',
        'quote_pdf_original_name',
        'quote_pdf_mime_type',
        'quote_pdf_file_size',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
        'quote_pdf_file_size' => 'integer',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_RECEIVED => 'Recebida',
            self::STATUS_REJECTED => 'Rejeitada',
            self::STATUS_SELECTED => 'Selecionada',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
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
        return $this->hasMany(PurchaseQuoteItem::class)
            ->orderBy('purchase_request_item_id');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(PurchaseRequestAward::class, 'selected_quote_id')
            ->orderByDesc('decided_at');
    }

    public function preparedOrders(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrder::class, 'purchase_quote_id')
            ->orderByDesc('prepared_at');
    }

    public function hasQuotePdf(): bool
    {
        return ! empty($this->quote_pdf_path);
    }

    public function quotePdfExists(): bool
    {
        if (! $this->hasQuotePdf()) {
            return false;
        }

        return Storage::disk($this->quote_pdf_disk ?: 'local')->exists($this->quote_pdf_path);
    }
}

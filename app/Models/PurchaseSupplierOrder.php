<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseSupplierOrder extends Model
{
    public const STATUS_PREPARED = 'prepared';

    protected $fillable = [
        'purchase_request_id',
        'award_id',
        'supplier_id',
        'purchase_quote_id',
        'payment_term_id',
        'currency',
        'status',
        'subtotal_amount',
        'notes',
        'prepared_at',
        'prepared_by',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'prepared_at' => 'datetime',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestAward::class, 'award_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuote::class, 'purchase_quote_id');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseSupplierOrderItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}


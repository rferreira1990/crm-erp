<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseSupplierOrderReturnEmailLog extends Model
{
    protected $fillable = [
        'owner_id',
        'purchase_supplier_order_return_id',
        'user_id',
        'recipient_name',
        'recipient_email',
        'cc_email',
        'bcc_email',
        'subject',
        'body_snapshot',
        'is_resend',
        'sent_at',
    ];

    protected $casts = [
        'is_resend' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseSupplierOrderReturn::class, 'purchase_supplier_order_return_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}


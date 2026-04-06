<?php

namespace App\Http\Requests\Purchases;

class UpdatePurchaseSupplierOrderRequest extends StorePurchaseSupplierOrderRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }
}


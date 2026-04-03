<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierOrder;
use App\Models\PurchaseSupplierOrderReceipt;
use App\Models\User;

class PurchaseSupplierOrderReceiptPolicy
{
    public function viewAny(User $user, PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $purchaseRequest->owner_id)
            && (int) $order->purchase_request_id === (int) $purchaseRequest->id;
    }

    public function view(User $user, PurchaseSupplierOrderReceipt $receipt): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $receipt->owner_id);
    }

    public function create(User $user, PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $purchaseRequest->owner_id)
            && (int) $order->purchase_request_id === (int) $purchaseRequest->id;
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}

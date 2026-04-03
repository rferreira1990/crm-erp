<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierOrder;
use App\Models\PurchaseSupplierOrderReturn;
use App\Models\User;

class PurchaseSupplierOrderReturnPolicy
{
    public function viewAny(User $user, PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $purchaseRequest->owner_id)
            && (int) $order->purchase_request_id === (int) $purchaseRequest->id;
    }

    public function view(User $user, PurchaseSupplierOrderReturn $return): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $return->owner_id);
    }

    public function create(User $user, PurchaseRequest $purchaseRequest, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $purchaseRequest->owner_id)
            && (int) $order->purchase_request_id === (int) $purchaseRequest->id;
    }

    public function close(User $user, PurchaseSupplierOrderReturn $return): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $return->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}

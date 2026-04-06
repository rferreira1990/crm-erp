<?php

namespace App\Policies;

use App\Models\PurchaseSupplierOrder;
use App\Models\PurchaseSupplierOrderReceipt;
use App\Models\User;

class PurchaseSupplierOrderReceiptPolicy
{
    public function viewAny(User $user, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $order->owner_id);
    }

    public function view(User $user, PurchaseSupplierOrderReceipt $receipt): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $receipt->owner_id);
    }

    public function create(User $user, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $order->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}

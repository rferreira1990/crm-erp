<?php

namespace App\Policies;

use App\Models\PurchaseSupplierOrder;
use App\Models\PurchaseSupplierOrderReturn;
use App\Models\User;

class PurchaseSupplierOrderReturnPolicy
{
    public function viewAny(User $user, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $order->owner_id);
    }

    public function view(User $user, PurchaseSupplierOrderReturn $return): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $return->owner_id);
    }

    public function create(User $user, PurchaseSupplierOrder $order): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $order->owner_id);
    }

    public function close(User $user, PurchaseSupplierOrderReturn $return): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $return->owner_id);
    }

    public function sendEmail(User $user, PurchaseSupplierOrderReturn $return): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $return->owner_id);
    }

    public function updateConfirmation(User $user, PurchaseSupplierOrderReturn $return): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $return->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}

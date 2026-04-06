<?php

namespace App\Policies;

use App\Models\PurchaseDirectPurchase;
use App\Models\User;

class PurchaseDirectPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.view');
    }

    public function view(User $user, PurchaseDirectPurchase $purchase): bool
    {
        return $user->can('purchases.view')
            && $this->belongsToUserTenant($user, (int) $purchase->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.create');
    }

    public function update(User $user, PurchaseDirectPurchase $purchase): bool
    {
        return $user->can('purchases.update')
            && $this->belongsToUserTenant($user, (int) $purchase->owner_id);
    }

    public function delete(User $user, PurchaseDirectPurchase $purchase): bool
    {
        return $user->can('purchases.delete')
            && $this->belongsToUserTenant($user, (int) $purchase->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}


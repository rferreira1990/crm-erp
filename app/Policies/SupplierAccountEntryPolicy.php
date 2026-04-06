<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\SupplierAccountEntry;
use App\Models\User;

class SupplierAccountEntryPolicy
{
    public function viewAny(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.view')
            && $this->belongsToUserTenant($user, (int) $supplier->owner_id);
    }

    public function view(User $user, SupplierAccountEntry $entry): bool
    {
        return $user->can('suppliers.view')
            && $this->belongsToUserTenant($user, (int) $entry->owner_id);
    }

    public function create(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.update')
            && $this->belongsToUserTenant($user, (int) $supplier->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}


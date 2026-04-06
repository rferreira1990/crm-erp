<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class SupplierPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.view')
            && $this->belongsToUserTenant($user, $supplier->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.update')
            && $this->belongsToUserTenant($user, $supplier->owner_id);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete')
            && $this->belongsToUserTenant($user, $supplier->owner_id);
    }
}

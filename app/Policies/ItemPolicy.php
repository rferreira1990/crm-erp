<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class ItemPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('items.view');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->can('items.view')
            && $this->belongsToUserTenantOrShared($user, $item->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('items.create');
    }

    public function import(User $user): bool
    {
        return $user->can('items.create') && $user->can('items.edit');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('items.edit')
            && $this->belongsToUserTenant($user, $item->owner_id);
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('items.delete')
            && $this->belongsToUserTenant($user, $item->owner_id);
    }
}

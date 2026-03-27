<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('items.view');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->can('items.view')
            && (int) $item->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('items.create');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('items.edit')
            && (int) $item->owner_id === (int) $user->id;
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('items.delete')
            && (int) $item->owner_id === (int) $user->id;
    }
}

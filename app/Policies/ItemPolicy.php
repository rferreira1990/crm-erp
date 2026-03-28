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
        return $user->can('items.view');
    }

    public function create(User $user): bool
    {
        return $user->can('items.create');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('items.edit');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('items.delete');
    }
}

<?php

namespace App\Policies;

use App\Models\ItemFamily;
use App\Models\User;

class ItemFamilyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, ItemFamily $itemFamily): bool
    {
        return $user->can('settings.manage')
            && (int) $itemFamily->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }
}

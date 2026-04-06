<?php

namespace App\Policies;

use App\Models\ItemFamily;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class ItemFamilyPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, ItemFamily $itemFamily): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenantOrShared($user, $itemFamily->owner_id);
    }

    public function update(User $user, ItemFamily $itemFamily): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $itemFamily->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, ItemFamily $itemFamily): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $itemFamily->owner_id);
    }
}

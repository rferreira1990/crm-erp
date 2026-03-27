<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('settings.manage')
            && (int) $brand->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('settings.manage')
            && (int) $brand->owner_id === (int) $user->id;
    }
}

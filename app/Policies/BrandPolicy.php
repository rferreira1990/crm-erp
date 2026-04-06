<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksTenantOwnership;
use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenantOrShared($user, $brand->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $brand->owner_id);
    }
}

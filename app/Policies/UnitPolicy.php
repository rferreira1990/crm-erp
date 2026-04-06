<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class UnitPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenantOrShared($user, $unit->owner_id);
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $unit->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $unit->owner_id);
    }
}

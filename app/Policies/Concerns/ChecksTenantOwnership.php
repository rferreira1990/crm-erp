<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksTenantOwnership
{
    protected function belongsToUserTenant(User $user, int|string|null $ownerId): bool
    {
        if ($ownerId === null || $ownerId === '') {
            return false;
        }

        return (int) $ownerId === (int) $user->id;
    }

    protected function belongsToUserTenantOrShared(User $user, int|string|null $ownerId): bool
    {
        return $ownerId === null || $ownerId === '' || (int) $ownerId === (int) $user->id;
    }
}

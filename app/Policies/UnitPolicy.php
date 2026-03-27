<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, Unit $unit): bool
    {
        if ($unit->owner_id === null) {
            return true; // unidades globais
        }

        return $user->can('settings.manage')
            && (int) $unit->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }
}

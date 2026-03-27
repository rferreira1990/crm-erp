<?php

namespace App\Policies;

use App\Models\TaxExemptionReason;
use App\Models\User;

class TaxExemptionReasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, TaxExemptionReason $reason): bool
    {
        return $user->can('settings.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }
}

<?php

namespace App\Policies;

use App\Models\TaxRate;
use App\Models\User;

class TaxRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, TaxRate $taxRate): bool
    {
        return $user->can('settings.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }
}

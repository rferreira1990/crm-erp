<?php

namespace App\Policies;

use App\Models\TaxRate;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class TaxRatePolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, TaxRate $taxRate): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenantOrShared($user, $taxRate->owner_id);
    }

    public function update(User $user, TaxRate $taxRate): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $taxRate->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, TaxRate $taxRate): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $taxRate->owner_id);
    }
}

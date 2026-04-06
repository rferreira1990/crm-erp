<?php

namespace App\Policies;

use App\Models\TaxExemptionReason;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class TaxExemptionReasonPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, TaxExemptionReason $reason): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenantOrShared($user, $reason->owner_id);
    }

    public function update(User $user, TaxExemptionReason $reason): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $reason->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, TaxExemptionReason $reason): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $reason->owner_id);
    }
}

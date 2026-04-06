<?php

namespace App\Policies;

use App\Models\PaymentTerm;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class PaymentTermPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenantOrShared($user, $paymentTerm->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $paymentTerm->owner_id);
    }

    public function delete(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $paymentTerm->owner_id);
    }
}

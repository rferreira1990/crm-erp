<?php

namespace App\Policies;

use App\Models\PaymentTerm;
use App\Models\User;

class PaymentTermPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage')
            && $paymentTerm->owner_id !== null
            && (int) $paymentTerm->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage')
            && $paymentTerm->owner_id !== null
            && (int) $paymentTerm->owner_id === (int) $user->id;
    }

    public function delete(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage')
            && $paymentTerm->owner_id !== null
            && (int) $paymentTerm->owner_id === (int) $user->id;
    }
}

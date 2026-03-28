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
        return $user->can('settings.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, PaymentTerm $paymentTerm): bool
    {
        return $user->can('settings.manage');
    }
}

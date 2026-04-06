<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\CustomerAccountEntry;
use App\Models\User;

class CustomerAccountEntryPolicy
{
    public function viewAny(User $user, Customer $customer): bool
    {
        return $user->can('customers.view')
            && $this->belongsToUserTenant($user, (int) $customer->owner_id);
    }

    public function view(User $user, CustomerAccountEntry $entry): bool
    {
        return $user->can('customers.view')
            && $this->belongsToUserTenant($user, (int) $entry->owner_id);
    }

    public function create(User $user, Customer $customer): bool
    {
        return $user->can('customers.edit')
            && $this->belongsToUserTenant($user, (int) $customer->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}


<?php

namespace App\Policies;

use App\Models\CustomerReceivable;
use App\Models\User;

class CustomerReceivablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, CustomerReceivable $receivable): bool
    {
        return $user->can('customers.view')
            && $this->belongsToUserTenant($user, (int) $receivable->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('customers.edit');
    }

    public function update(User $user, CustomerReceivable $receivable): bool
    {
        return $user->can('customers.edit')
            && $this->belongsToUserTenant($user, (int) $receivable->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}

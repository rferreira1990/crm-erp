<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view') && $this->canAccessCustomer($user, $customer);
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.edit') && $this->canAccessCustomer($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete') && $this->canAccessCustomer($user, $customer);
    }

    private function canAccessCustomer(User $user, Customer $customer): bool
    {
        if ($customer->owner_id !== null) {
            return (int) $customer->owner_id === (int) $user->id;
        }

        return (int) ($customer->created_by ?? 0) === (int) $user->id;
    }
}

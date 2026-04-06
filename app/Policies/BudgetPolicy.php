<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class BudgetPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('budgets.view');
    }

    public function view(User $user, Budget $budget): bool
    {
        return $user->can('budgets.view')
            && $this->belongsToUserTenant($user, $budget->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('budgets.create');
    }

    public function update(User $user, Budget $budget): bool
    {
        return $user->can('budgets.update')
            && $this->belongsToUserTenant($user, $budget->owner_id);
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $user->can('budgets.delete')
            && $this->belongsToUserTenant($user, $budget->owner_id);
    }
}

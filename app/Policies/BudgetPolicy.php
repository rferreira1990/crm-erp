<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('budgets.view');
    }

    public function view(User $user, Budget $budget): bool
    {
        return $user->can('budgets.view');
    }

    public function create(User $user): bool
    {
        return $user->can('budgets.create');
    }

    public function update(User $user, Budget $budget): bool
    {
        return $user->can('budgets.update');
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $user->can('budgets.delete');
    }
}

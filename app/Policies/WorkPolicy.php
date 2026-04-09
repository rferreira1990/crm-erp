<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;

class WorkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('works.view');
    }

    public function view(User $user, Work $work): bool
    {
        return $user->can('works.view');
    }

    public function create(User $user): bool
    {
        return $user->can('works.create');
    }

    public function update(User $user, Work $work): bool
    {
        return $user->can('works.update');
    }

    public function delete(User $user, Work $work): bool
    {
        return $user->can('works.delete');
    }
}

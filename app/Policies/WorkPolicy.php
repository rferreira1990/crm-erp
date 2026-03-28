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
        return $user->can('works.view')
            && $work->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('works.create');
    }

    public function update(User $user, Work $work): bool
    {
        return $user->can('works.update')
            && $work->owner_id === $user->id;
    }

    public function delete(User $user, Work $work): bool
    {
        return $user->can('works.delete')
            && $work->owner_id === $user->id;
    }
}

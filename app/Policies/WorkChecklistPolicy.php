<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkChecklist;

class WorkChecklistPolicy
{
    public function viewAny(User $user, Work $work): bool
    {
        return $user->can('works.view');
    }

    public function view(User $user, WorkChecklist $checklist): bool
    {
        return $user->can('works.view');
    }

    public function create(User $user, Work $work): bool
    {
        return $user->can('works.update');
    }

    public function update(User $user, WorkChecklist $checklist): bool
    {
        return $user->can('works.update');
    }

    public function delete(User $user, WorkChecklist $checklist): bool
    {
        return $user->can('works.update');
    }
}

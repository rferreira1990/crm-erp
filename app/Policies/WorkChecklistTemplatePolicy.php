<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkChecklistTemplate;

class WorkChecklistTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('works.view');
    }

    public function view(User $user, WorkChecklistTemplate $template): bool
    {
        return $user->can('works.view');
    }

    public function create(User $user): bool
    {
        return $user->can('works.update');
    }

    public function update(User $user, WorkChecklistTemplate $template): bool
    {
        return $user->can('works.update');
    }

    public function delete(User $user, WorkChecklistTemplate $template): bool
    {
        return $user->can('works.update');
    }
}

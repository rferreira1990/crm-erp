<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkFile;

class WorkFilePolicy
{
    public function viewAny(User $user, Work $work): bool
    {
        return $user->can('works.view');
    }

    public function view(User $user, WorkFile $workFile): bool
    {
        return $user->can('works.view');
    }

    public function create(User $user, Work $work): bool
    {
        return $user->can('works.update');
    }

    public function delete(User $user, WorkFile $workFile): bool
    {
        return $user->can('works.update');
    }
}

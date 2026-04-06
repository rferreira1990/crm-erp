<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksTenantOwnership;
use App\Models\User;
use App\Models\Work;

class WorkPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('works.view');
    }

    public function view(User $user, Work $work): bool
    {
        return $user->can('works.view')
            && $this->belongsToUserTenant($user, $work->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('works.create');
    }

    public function update(User $user, Work $work): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, $work->owner_id);
    }

    public function delete(User $user, Work $work): bool
    {
        return $user->can('works.delete')
            && $this->belongsToUserTenant($user, $work->owner_id);
    }
}

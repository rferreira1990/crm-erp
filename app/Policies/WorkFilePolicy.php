<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkFile;

class WorkFilePolicy
{
    public function viewAny(User $user, Work $work): bool
    {
        return $user->can('works.view')
            && $this->belongsToUserTenant($user, (int) $work->owner_id);
    }

    public function view(User $user, WorkFile $workFile): bool
    {
        return $user->can('works.view')
            && $this->belongsToUserTenant($user, (int) $workFile->owner_id);
    }

    public function create(User $user, Work $work): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $work->owner_id);
    }

    public function delete(User $user, WorkFile $workFile): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $workFile->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}


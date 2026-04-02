<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkChecklist;

class WorkChecklistPolicy
{
    public function viewAny(User $user, Work $work): bool
    {
        return $user->can('works.view')
            && $this->belongsToUserTenant($user, (int) $work->owner_id);
    }

    public function view(User $user, WorkChecklist $checklist): bool
    {
        return $user->can('works.view')
            && $this->belongsToUserTenant($user, (int) $checklist->owner_id);
    }

    public function create(User $user, Work $work): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $work->owner_id);
    }

    public function update(User $user, WorkChecklist $checklist): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $checklist->owner_id);
    }

    public function delete(User $user, WorkChecklist $checklist): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $checklist->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}


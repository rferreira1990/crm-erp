<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkChecklist;
use App\Models\WorkChecklistItem;

class WorkChecklistItemPolicy
{
    public function create(User $user, WorkChecklist $checklist, Work $work): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $checklist->owner_id)
            && (int) $checklist->work_id === (int) $work->id;
    }

    public function update(User $user, WorkChecklistItem $item): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $item->owner_id);
    }

    public function delete(User $user, WorkChecklistItem $item): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $item->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}


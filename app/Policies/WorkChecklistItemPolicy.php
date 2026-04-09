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
            && (int) $checklist->work_id === (int) $work->id;
    }

    public function update(User $user, WorkChecklistItem $item): bool
    {
        return $user->can('works.update');
    }

    public function delete(User $user, WorkChecklistItem $item): bool
    {
        return $user->can('works.update');
    }
}

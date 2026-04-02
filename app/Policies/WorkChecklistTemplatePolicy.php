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
        return $user->can('works.view')
            && $this->belongsToUserTenant($user, (int) $template->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('works.update');
    }

    public function update(User $user, WorkChecklistTemplate $template): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $template->owner_id);
    }

    public function delete(User $user, WorkChecklistTemplate $template): bool
    {
        return $user->can('works.update')
            && $this->belongsToUserTenant($user, (int) $template->owner_id);
    }

    private function belongsToUserTenant(User $user, int $ownerId): bool
    {
        return $ownerId > 0 && $ownerId === (int) $user->id;
    }
}

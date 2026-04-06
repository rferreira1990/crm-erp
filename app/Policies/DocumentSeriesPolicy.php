<?php

namespace App\Policies;

use App\Models\DocumentSeries;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class DocumentSeriesPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $documentSeries->owner_id);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $documentSeries->owner_id);
    }

    public function delete(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('settings.manage')
            && $this->belongsToUserTenant($user, $documentSeries->owner_id);
    }
}

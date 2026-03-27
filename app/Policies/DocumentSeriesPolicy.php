<?php

namespace App\Policies;

use App\Models\DocumentSeries;
use App\Models\User;

class DocumentSeriesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('settings.manage')
            && (int) $documentSeries->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('settings.manage')
            && (int) $documentSeries->owner_id === (int) $user->id;
    }

    public function delete(User $user, DocumentSeries $documentSeries): bool
    {
        return $user->can('settings.manage')
            && (int) $documentSeries->owner_id === (int) $user->id;
    }
}

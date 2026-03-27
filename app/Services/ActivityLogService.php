<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        ?array $payload = null,
        ?int $ownerId = null,
        ?int $userId = null
    ): void {
        ActivityLog::create([
            'owner_id' => $ownerId ?? Auth::id(),
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'payload' => $payload,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogs\IndexActivityLogRequest;
use App\Models\ActivityLog;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(IndexActivityLogRequest $request): View
    {
        $filters = $request->filters();

        $query = ActivityLog::query()
            ->with(['user:id,name,email'])
            ->where('owner_id', Auth::id());

        if (! empty($filters['entity'])) {
            $query->where('entity', $filters['entity']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('entity', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('entity_id', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $activityLogs = $query
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $entities = ActivityLog::query()
            ->where('owner_id', Auth::id())
            ->select('entity')
            ->distinct()
            ->orderBy('entity')
            ->pluck('entity');

        $actions = ActivityLog::query()
            ->where('owner_id', Auth::id())
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('activity-logs.index', [
            'activityLogs' => $activityLogs,
            'entities' => $entities,
            'actions' => $actions,
            'filters' => $filters,
        ]);
    }
}

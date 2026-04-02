<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\ApplyWorkChecklistTemplateRequest;
use App\Http\Requests\Works\StoreWorkChecklistRequest;
use App\Models\Work;
use App\Models\WorkChecklist;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkChecklistController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function store(StoreWorkChecklistRequest $request, Work $work): RedirectResponse
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('create', [WorkChecklist::class, $work]);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();

        $checklist = WorkChecklist::query()->create([
            'owner_id' => $work->owner_id,
            'work_id' => $work->id,
            'name' => $validated['checklist_name'],
            'description' => $validated['checklist_description'] ?? null,
        ]);

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_checklist',
            entityId: $checklist->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'name' => $checklist->name,
                'description' => $checklist->description,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.checklists.index', $work)
            ->with('success', 'Checklist criada com sucesso.');
    }

    public function index(Work $work): View
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('viewAny', [WorkChecklist::class, $work]);

        $work->load([
            'checklists.items.completedBy:id,name',
        ]);

        $checklistTemplates = collect(config('work_checklists.templates', []))
            ->map(function (array $template, string $key): array {
                $items = collect($template['items'] ?? [])
                    ->map(function (mixed $item): array {
                        if (is_array($item)) {
                            return [
                                'description' => trim((string) ($item['description'] ?? '')),
                                'is_required' => (bool) ($item['is_required'] ?? false),
                            ];
                        }

                        return [
                            'description' => trim((string) $item),
                            'is_required' => false,
                        ];
                    })
                    ->filter(fn (array $item): bool => $item['description'] !== '')
                    ->values();

                return [
                    'key' => $key,
                    'name' => trim((string) ($template['name'] ?? $key)),
                    'description' => trim((string) ($template['description'] ?? '')),
                    'items_count' => $items->count(),
                ];
            })
            ->filter(fn (array $template): bool => $template['name'] !== '')
            ->values();

        return view('works.checklists.index', [
            'work' => $work,
            'checklists' => $work->checklists,
            'checklistTemplates' => $checklistTemplates,
        ]);
    }

    public function applyTemplate(ApplyWorkChecklistTemplateRequest $request, Work $work): RedirectResponse
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('create', [WorkChecklist::class, $work]);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $validated = $request->validated();
        $templateKey = $validated['template_key'];
        $template = config('work_checklists.templates.' . $templateKey);

        if (! is_array($template)) {
            return redirect()
                ->route('works.checklists.index', $work)
                ->with('error', 'Template de checklist invalido.');
        }

        $templateName = trim((string) ($template['name'] ?? $templateKey));
        $templateDescription = trim((string) ($template['description'] ?? ''));
        $templateItems = collect($template['items'] ?? [])
            ->map(function (mixed $item): array {
                if (is_array($item)) {
                    return [
                        'description' => trim((string) ($item['description'] ?? '')),
                        'is_required' => (bool) ($item['is_required'] ?? false),
                    ];
                }

                return [
                    'description' => trim((string) $item),
                    'is_required' => false,
                ];
            })
            ->filter(fn (array $item): bool => $item['description'] !== '')
            ->values();

        if ($templateName === '' || $templateItems->isEmpty()) {
            return redirect()
                ->route('works.checklists.index', $work)
                ->with('error', 'Template de checklist invalido ou sem itens.');
        }

        $alreadyExists = WorkChecklist::query()
            ->where('owner_id', $work->owner_id)
            ->where('work_id', $work->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($templateName)])
            ->exists();

        if ($alreadyExists) {
            return redirect()
                ->route('works.checklists.index', $work)
                ->with('error', 'Ja existe uma checklist com este nome nesta obra.');
        }

        $createdChecklist = DB::transaction(function () use (
            $work,
            $templateName,
            $templateDescription,
            $templateItems
        ): WorkChecklist {
            $checklist = WorkChecklist::query()->create([
                'owner_id' => $work->owner_id,
                'work_id' => $work->id,
                'name' => $templateName,
                'description' => $templateDescription !== '' ? $templateDescription : null,
            ]);

            foreach ($templateItems as $templateItem) {
                $checklist->items()->create([
                    'owner_id' => $work->owner_id,
                    'description' => $templateItem['description'],
                    'is_required' => (bool) $templateItem['is_required'],
                    'is_completed' => false,
                    'completed_by' => null,
                    'completed_at' => null,
                ]);
            }

            return $checklist->load('items');
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_checklist',
            entityId: $createdChecklist->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'template_key' => $templateKey,
                'name' => $createdChecklist->name,
                'description' => $createdChecklist->description,
                'items_total' => $createdChecklist->items->count(),
                'required_items_total' => $createdChecklist->items->where('is_required', true)->count(),
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.checklists.index', $work)
            ->with('success', 'Checklist default carregada com sucesso.');
    }

    public function destroy(Work $work, WorkChecklist $checklist): RedirectResponse
    {
        $this->ensureChecklistRouteScope($work, $checklist);
        $this->authorize('delete', $checklist);

        if (! $work->isEditable()) {
            return $this->nonEditableResponse($work);
        }

        $payload = [
            'work_id' => $work->id,
            'work_code' => $work->code,
            'name' => $checklist->name,
            'items_total' => $checklist->items()->count(),
        ];

        $checklist->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_checklist',
            entityId: $checklist->id,
            payload: $payload,
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('works.checklists.index', $work)
            ->with('success', 'Checklist removida com sucesso.');
    }

    private function ensureWorkRouteScope(Work $work): void
    {
        abort_if((int) $work->owner_id !== (int) Auth::id(), 404);
    }

    private function ensureChecklistRouteScope(Work $work, WorkChecklist $checklist): void
    {
        if ((int) $checklist->work_id !== (int) $work->id) {
            abort(404);
        }

        if ((int) $checklist->owner_id !== (int) $work->owner_id) {
            abort(404);
        }

        $this->ensureWorkRouteScope($work);
    }

    private function nonEditableResponse(Work $work): RedirectResponse
    {
        return redirect()
            ->route('works.checklists.index', $work)
            ->with('error', 'Obra concluida ou cancelada. Nao e permitido alterar registos operacionais.');
    }
}

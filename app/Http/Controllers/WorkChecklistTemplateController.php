<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkChecklistTemplates\StoreWorkChecklistTemplateRequest;
use App\Http\Requests\WorkChecklistTemplates\UpdateWorkChecklistTemplateRequest;
use App\Models\WorkChecklistTemplate;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkChecklistTemplateController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', WorkChecklistTemplate::class);

        $ownerId = (int) Auth::id();

        $templates = WorkChecklistTemplate::query()
            ->forOwner($ownerId)
            ->withCount('items')
            ->with('items')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaultTemplates = $this->defaultTemplates();

        return view('work-checklist-templates.index', [
            'templates' => $templates,
            'defaultTemplates' => $defaultTemplates,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', WorkChecklistTemplate::class);

        return view('work-checklist-templates.create', [
            'template' => new WorkChecklistTemplate([
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'templateItems' => collect([
                ['description' => '', 'is_required' => false, 'sort_order' => 0],
            ]),
        ]);
    }

    public function store(StoreWorkChecklistTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkChecklistTemplate::class);

        $ownerId = (int) Auth::id();
        $validated = $request->validated();

        $this->ensureTemplateNameIsUnique($ownerId, $validated['name']);

        $template = DB::transaction(function () use ($validated, $ownerId): WorkChecklistTemplate {
            $template = WorkChecklistTemplate::query()->create([
                'owner_id' => $ownerId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) $validated['is_active'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ]);

            $this->syncTemplateItems($template, collect($validated['items']));

            return $template->load('items');
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'work_checklist_template',
            entityId: $template->id,
            payload: [
                'name' => $template->name,
                'description' => $template->description,
                'is_active' => (bool) $template->is_active,
                'sort_order' => (int) $template->sort_order,
                'items_total' => $template->items->count(),
                'required_items_total' => $template->items->where('is_required', true)->count(),
            ],
            ownerId: $ownerId,
            userId: $ownerId,
        );

        return redirect()
            ->route('work-checklist-templates.index')
            ->with('success', 'Template de checklist criado com sucesso.');
    }

    public function edit(WorkChecklistTemplate $template): View
    {
        $this->ensureTemplateRouteScope($template);
        $this->authorize('update', $template);

        $template->load('items');

        $templateItems = $template->items
            ->map(fn ($item): array => [
                'description' => (string) $item->description,
                'is_required' => (bool) $item->is_required,
                'sort_order' => (int) $item->sort_order,
            ]);

        if ($templateItems->isEmpty()) {
            $templateItems = collect([
                ['description' => '', 'is_required' => false, 'sort_order' => 0],
            ]);
        }

        return view('work-checklist-templates.edit', [
            'template' => $template,
            'templateItems' => $templateItems,
        ]);
    }

    public function update(UpdateWorkChecklistTemplateRequest $request, WorkChecklistTemplate $template): RedirectResponse
    {
        $this->ensureTemplateRouteScope($template);
        $this->authorize('update', $template);

        $validated = $request->validated();
        $ownerId = (int) Auth::id();

        $this->ensureTemplateNameIsUnique($ownerId, $validated['name'], (int) $template->id);

        $oldPayload = [
            'name' => $template->name,
            'description' => $template->description,
            'is_active' => (bool) $template->is_active,
            'sort_order' => (int) $template->sort_order,
            'items_total' => $template->items()->count(),
        ];

        DB::transaction(function () use ($template, $validated, $ownerId): void {
            $template->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) $validated['is_active'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'updated_by' => $ownerId,
            ]);

            $this->syncTemplateItems($template, collect($validated['items']));
        });

        $template->refresh()->load('items');

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'work_checklist_template',
            entityId: $template->id,
            payload: [
                'old' => $oldPayload,
                'new' => [
                    'name' => $template->name,
                    'description' => $template->description,
                    'is_active' => (bool) $template->is_active,
                    'sort_order' => (int) $template->sort_order,
                    'items_total' => $template->items->count(),
                    'required_items_total' => $template->items->where('is_required', true)->count(),
                ],
            ],
            ownerId: $ownerId,
            userId: $ownerId,
        );

        return redirect()
            ->route('work-checklist-templates.index')
            ->with('success', 'Template de checklist atualizado com sucesso.');
    }

    public function destroy(WorkChecklistTemplate $template): RedirectResponse
    {
        $this->ensureTemplateRouteScope($template);
        $this->authorize('delete', $template);

        $ownerId = (int) Auth::id();
        $template->load('items');

        $payload = [
            'name' => $template->name,
            'items_total' => $template->items->count(),
            'required_items_total' => $template->items->where('is_required', true)->count(),
        ];

        $template->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_checklist_template',
            entityId: $template->id,
            payload: $payload,
            ownerId: $ownerId,
            userId: $ownerId,
        );

        return redirect()
            ->route('work-checklist-templates.index')
            ->with('success', 'Template de checklist removido com sucesso.');
    }

    public function loadDefaults(): RedirectResponse
    {
        $this->authorize('create', WorkChecklistTemplate::class);

        $ownerId = (int) Auth::id();
        $defaultTemplates = $this->defaultTemplates();

        if ($defaultTemplates->isEmpty()) {
            return redirect()
                ->route('work-checklist-templates.index')
                ->with('error', 'Nao existem templates base configurados.');
        }

        $existingNames = WorkChecklistTemplate::query()
            ->forOwner($ownerId)
            ->pluck('name')
            ->map(fn (string $name): string => mb_strtolower(trim($name)))
            ->filter(fn (string $name): bool => $name !== '')
            ->all();

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $defaultTemplates,
            $existingNames,
            $ownerId,
            &$created,
            &$skipped
        ): void {
            $knownNames = $existingNames;

            foreach ($defaultTemplates as $position => $template) {
                $normalizedName = mb_strtolower($template['name']);

                if (in_array($normalizedName, $knownNames, true)) {
                    $skipped++;
                    continue;
                }

                $model = WorkChecklistTemplate::query()->create([
                    'owner_id' => $ownerId,
                    'name' => $template['name'],
                    'description' => $template['description'] ?: null,
                    'is_active' => true,
                    'sort_order' => $position * 10,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);

                $this->syncTemplateItems($model, collect($template['items']));
                $created++;
                $knownNames[] = $normalizedName;

                $this->activityLogService->log(
                    action: ActivityActions::CREATED,
                    entity: 'work_checklist_template',
                    entityId: $model->id,
                    payload: [
                        'origin' => 'default_templates',
                        'name' => $model->name,
                        'items_total' => $model->items()->count(),
                    ],
                    ownerId: $ownerId,
                    userId: $ownerId,
                );
            }
        });

        return redirect()
            ->route('work-checklist-templates.index')
            ->with('success', 'Templates base carregados. Criados: ' . $created . '. Ignorados: ' . $skipped . '.');
    }

    private function ensureTemplateRouteScope(WorkChecklistTemplate $template): void
    {
        abort_if((int) $template->owner_id !== (int) Auth::id(), 404);
    }

    private function ensureTemplateNameIsUnique(int $ownerId, string $name, ?int $ignoreId = null): void
    {
        $normalized = mb_strtolower(trim($name));

        $exists = WorkChecklistTemplate::query()
            ->forOwner($ownerId)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get(['id', 'name'])
            ->contains(fn (WorkChecklistTemplate $template): bool => mb_strtolower(trim($template->name)) === $normalized);

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Ja existe um template com este nome.',
            ]);
        }
    }

    private function syncTemplateItems(WorkChecklistTemplate $template, Collection $items): void
    {
        $template->items()->delete();

        $items
            ->sortBy('sort_order')
            ->values()
            ->each(function (array $item, int $position) use ($template): void {
                $template->items()->create([
                    'owner_id' => $template->owner_id,
                    'description' => $item['description'],
                    'is_required' => (bool) ($item['is_required'] ?? false),
                    'sort_order' => (int) ($item['sort_order'] ?? ($position + 1)),
                ]);
            });
    }

    private function defaultTemplates(): Collection
    {
        return collect(config('work_checklists.templates', []))
            ->map(function (array $template): array {
                $name = trim((string) ($template['name'] ?? ''));
                $description = trim((string) ($template['description'] ?? ''));
                $items = collect($template['items'] ?? [])
                    ->map(function (mixed $item, int $position): array {
                        if (is_array($item)) {
                            return [
                                'description' => trim((string) ($item['description'] ?? '')),
                                'is_required' => (bool) ($item['is_required'] ?? false),
                                'sort_order' => (int) ($item['sort_order'] ?? (($position + 1) * 10)),
                            ];
                        }

                        return [
                            'description' => trim((string) $item),
                            'is_required' => false,
                            'sort_order' => ($position + 1) * 10,
                        ];
                    })
                    ->filter(fn (array $item): bool => $item['description'] !== '')
                    ->values();

                return [
                    'name' => $name,
                    'description' => $description,
                    'items' => $items,
                ];
            })
            ->filter(fn (array $template): bool => $template['name'] !== '' && $template['items']->isNotEmpty())
            ->values();
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Works\ChangeWorkStatusAction;
use App\Http\Requests\StoreWorkRequest;
use App\Http\Requests\UpdateWorkRequest;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Work::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => trim((string) $request->input('status', '')),
            'technical_manager_id' => trim((string) $request->input('technical_manager_id', '')),
            'date_from' => trim((string) $request->input('date_from', '')),
        ];

        $works = Work::query()
            ->with(['customer', 'technicalManager'])
            ->where('owner_id', Auth::id())
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('work_type', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['technical_manager_id'] !== '', function ($query) use ($filters) {
                $query->where('technical_manager_id', $filters['technical_manager_id']);
            })
            ->when($filters['date_from'] !== '', function ($query) use ($filters) {
                $query->whereDate('start_date_planned', '>=', $filters['date_from']);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('works.index', compact('works', 'filters', 'users'));
    }

    public function create()
    {
        $this->authorize('create', Work::class);

        $customers = Customer::query()
            ->where('owner_id', Auth::id())
            ->orderBy('name')
            ->get();

        $budgets = Budget::query()
            ->where('owner_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('works.create', compact('customers', 'budgets', 'users'));
    }

    public function store(StoreWorkRequest $request): RedirectResponse
    {
        $this->authorize('create', Work::class);

        $validated = $request->validated();

        $nextId = (Work::where('owner_id', Auth::id())->max('id') ?? 0) + 1;

        $work = Work::create([
            'owner_id' => Auth::id(),
            'customer_id' => $validated['customer_id'],
            'budget_id' => $validated['budget_id'] ?? null,
            'code' => 'OBR-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT),
            'name' => $validated['name'],
            'status' => Work::STATUS_PLANNED,
            'work_type' => $validated['work_type'] ?? null,
            'location' => $validated['location'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'city' => $validated['city'] ?? null,
            'start_date_planned' => $validated['start_date_planned'] ?? null,
            'end_date_planned' => $validated['end_date_planned'] ?? null,
            'start_date_actual' => $validated['start_date_actual'] ?? null,
            'end_date_actual' => $validated['end_date_actual'] ?? null,
            'technical_manager_id' => $validated['technical_manager_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $work->team()->sync($validated['team'] ?? []);

        WorkStatusHistory::create([
            'work_id' => $work->id,
            'old_status' => null,
            'new_status' => Work::STATUS_PLANNED,
            'notes' => 'Obra criada.',
            'changed_by' => Auth::id(),
        ]);

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Obra criada com sucesso.');
    }

    public function show(Work $work)
    {
        $this->authorize('view', $work);

        $work->load([
            'customer',
            'budget',
            'technicalManager',
            'team',
            'statusHistories.changedBy',
        ]);

        $availableStatuses = collect(Work::statuses())
            ->filter(fn ($label, $status) => $work->canChangeTo($status))
            ->all();

        return view('works.show', compact('work', 'availableStatuses'));
    }

    public function edit(Work $work)
    {
        $this->authorize('update', $work);

        $customers = Customer::query()
            ->where('owner_id', Auth::id())
            ->orderBy('name')
            ->get();

        $budgets = Budget::query()
            ->where('owner_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get();

        $work->load('team');

        return view('works.edit', compact('work', 'customers', 'budgets', 'users'));
    }

    public function update(UpdateWorkRequest $request, Work $work): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validated();

        $work->update([
            'customer_id' => $validated['customer_id'],
            'budget_id' => $validated['budget_id'] ?? null,
            'name' => $validated['name'],
            'work_type' => $validated['work_type'] ?? null,
            'location' => $validated['location'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'city' => $validated['city'] ?? null,
            'start_date_planned' => $validated['start_date_planned'] ?? null,
            'end_date_planned' => $validated['end_date_planned'] ?? null,
            'start_date_actual' => $validated['start_date_actual'] ?? null,
            'end_date_actual' => $validated['end_date_actual'] ?? null,
            'technical_manager_id' => $validated['technical_manager_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        $work->team()->sync($validated['team'] ?? []);

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Obra atualizada com sucesso.');
    }

    public function changeStatus(Request $request, Work $work, ChangeWorkStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $work);

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'status_notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'status' => 'estado',
            'status_notes' => 'observações',
        ]);

        try {
            $action->execute(
                work: $work,
                newStatus: $validated['status'],
                notes: $validated['status_notes'] ?? null
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('works.show', $work)
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('works.show', $work)
                ->with('error', 'Ocorreu um erro ao alterar o estado da obra.');
        }

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Estado da obra atualizado com sucesso.');
    }

    public function destroy(Work $work): RedirectResponse
    {
        $this->authorize('delete', $work);

        if (! $work->canBeDeleted()) {
            return redirect()
                ->route('works.show', $work)
                ->with('error', 'Só é possível apagar obras planeadas.');
        }

        $work->delete();

        return redirect()
            ->route('works.index')
            ->with('success', 'Obra apagada com sucesso.');
    }
}

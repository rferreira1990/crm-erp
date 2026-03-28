<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkRequest;
use App\Http\Requests\UpdateWorkRequest;
use App\Models\Customer;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Auth;

class WorkController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Work::class);

        $works = Work::query()
            ->with(['customer', 'technicalManager'])
            ->where('owner_id', Auth::id())
            ->latest('id')
            ->paginate(15);

        return view('works.index', compact('works'));
    }

    public function create()
    {
        $this->authorize('create', Work::class);

        $customers = Customer::where('owner_id', Auth::id())
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        return view('works.create', compact('customers', 'users'));
    }

    public function store(StoreWorkRequest $request)
    {
        $this->authorize('create', Work::class);

        $nextId = (Work::where('owner_id', Auth::id())->max('id') ?? 0) + 1;

        $work = Work::create([
            ...$request->validated(),
            'owner_id' => Auth::id(),
            'code' => 'OBR-' . now()->format('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT),
            'status' => Work::STATUS_PLANNED,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
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

        return view('works.show', compact('work'));
    }

    public function edit(Work $work)
    {
        $this->authorize('update', $work);

        $customers = Customer::where('owner_id', Auth::id())
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        return view('works.edit', compact('work', 'customers', 'users'));
    }

    public function update(UpdateWorkRequest $request, Work $work)
    {
        $this->authorize('update', $work);

        $work->update([
            ...$request->validated(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('works.show', $work)
            ->with('success', 'Obra atualizada com sucesso.');
    }

    public function destroy(Work $work)
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Unit::class);

        $units = Unit::query()
            ->orderBy('name')
            ->paginate(15);

        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        $this->authorize('create', Unit::class);

        return view('units.create');
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $this->authorize('create', Unit::class);

        Unit::create($request->validated());

        return redirect()->route('units.index')->with('success', 'Unidade criada com sucesso.');
    }

    public function edit(Unit $unit): View
    {
        $this->authorize('update', $unit);

        return view('units.edit', compact('unit'));
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $unit->update($request->validated());

        return redirect()->route('units.index')->with('success', 'Unidade atualizada com sucesso.');
    }
}

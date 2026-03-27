<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BrandController extends Controller
{
   public function index(): View
{
    $this->authorize('viewAny', Brand::class);

    $brands = Brand::query()
        ->where('owner_id', auth()->id())
        ->orderBy('name')
        ->paginate(15);

        return view('brands.index', compact('brands'));
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        Brand::create($request->validated());

        return redirect()->route('brands.index')->with('success', 'Marca criada com sucesso.');
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->authorize('update', $brand);

        $brand->update($request->validated());

        return redirect()->route('brands.index')->with('success', 'Marca atualizada com sucesso.');
    }
}

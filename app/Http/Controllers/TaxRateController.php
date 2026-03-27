<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxRateRequest;
use App\Http\Requests\UpdateTaxRateRequest;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxRateController extends Controller
{
   public function index(): View
    {
        $this->authorize('viewAny', TaxRate::class);

        $taxRates = TaxRate::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('tax-rates.index', compact('taxRates'));
    }

    public function create(): View
    {
        return view('tax-rates.create');
    }

    public function store(StoreTaxRateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_exempt'] = (float) $data['percent'] === 0.0;
        $data['is_default'] = false;
        $data['exemption_reason_id'] = null;

        TaxRate::create($data);

        return redirect()
            ->route('tax-rates.index')
            ->with('success', 'Taxa criada com sucesso.');
    }

    public function edit(TaxRate $tax_rate): View
    {
        return view('tax-rates.edit', compact('tax_rate'));
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $tax_rate): RedirectResponse
    {
        $data = $request->validated();
        $data['is_exempt'] = (float) $data['percent'] === 0.0;

        // Nesta fase mantemos estes campos fora do form
        unset($data['is_default'], $data['exemption_reason_id']);

        $tax_rate->update($data);

        return redirect()
            ->route('tax-rates.index')
            ->with('success', 'Taxa atualizada com sucesso.');
    }
}

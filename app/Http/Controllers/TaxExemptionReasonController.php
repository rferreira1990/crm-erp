<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxExemptionReasonRequest;
use App\Http\Requests\UpdateTaxExemptionReasonRequest;
use App\Models\TaxExemptionReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaxExemptionReasonController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TaxExemptionReason::class);

        $taxExemptionReasons = TaxExemptionReason::query()
            ->orderBy('code')
            ->paginate(15);

        return view('tax-exemption-reasons.index', compact('taxExemptionReasons'));
    }

    public function create(): View
    {
        return view('tax-exemption-reasons.create');
    }

    public function store(StoreTaxExemptionReasonRequest $request): RedirectResponse
    {
        TaxExemptionReason::create($request->validated());

        return redirect()
            ->route('tax-exemption-reasons.index')
            ->with('success', 'Motivo de isenção criado com sucesso.');
    }

    public function edit(TaxExemptionReason $tax_exemption_reason): View
    {
        return view('tax-exemption-reasons.edit', compact('tax_exemption_reason'));
    }

    public function update(UpdateTaxExemptionReasonRequest $request, TaxExemptionReason $tax_exemption_reason): RedirectResponse
    {
        $tax_exemption_reason->update($request->validated());

        return redirect()
            ->route('tax-exemption-reasons.index')
            ->with('success', 'Motivo de isenção atualizado com sucesso.');
    }
}

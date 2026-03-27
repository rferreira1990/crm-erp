<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentTermController extends Controller
{
    public function index()
    {
        $paymentTerms = PaymentTerm::query()
            ->visibleForOwner(Auth::id())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('payment-terms.index', compact('paymentTerms'));
    }

    public function create()
    {
        return view('payment-terms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'days' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        PaymentTerm::create([
            'owner_id' => Auth::id(),
            'name' => $validated['name'],
            'days' => $validated['days'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('payment-terms.index')
            ->with('success', 'Condição de pagamento criada com sucesso.');
    }

    public function edit(PaymentTerm $paymentTerm)
    {
        $this->authorizeAccess($paymentTerm);

        return view('payment-terms.edit', compact('paymentTerm'));
    }

    public function update(Request $request, PaymentTerm $paymentTerm): RedirectResponse
    {
        $this->authorizeAccess($paymentTerm);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'days' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $paymentTerm->update([
            'name' => $validated['name'],
            'days' => $validated['days'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()
            ->route('payment-terms.index')
            ->with('success', 'Condição de pagamento atualizada com sucesso.');
    }

    public function destroy(PaymentTerm $paymentTerm): RedirectResponse
    {
        $this->authorizeAccess($paymentTerm);

        if ($paymentTerm->budgets()->exists()) {
            return redirect()
                ->route('payment-terms.index')
                ->with('error', 'Não é possível apagar uma condição de pagamento já utilizada.');
        }

        $paymentTerm->delete();

        return redirect()
            ->route('payment-terms.index')
            ->with('success', 'Condição de pagamento apagada com sucesso.');
    }

    private function authorizeAccess(PaymentTerm $paymentTerm): void
    {
        if ($paymentTerm->owner_id === null) {
            abort(403);
        }

        abort_unless((int) $paymentTerm->owner_id === (int) Auth::id(), 403);
    }
}

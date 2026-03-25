<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\ChangeBudgetStatusAction;
use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetHeaderRequest;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Item;
use App\Models\TaxExemptionReason;
use App\Models\TaxRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $allowedStatuses = Budget::statuses();

        $budgets = Budget::query()
            ->with(['customer', 'creator'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('code', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('nif', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($status, $allowedStatuses, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(! empty($dateFrom), function ($query) use ($dateFrom) {
                $query->whereDate('budget_date', '>=', $dateFrom);
            })
            ->when(! empty($dateTo), function ($query) use ($dateTo) {
                $query->whereDate('budget_date', '<=', $dateTo);
            })
            ->latest('budget_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('budgets.index', [
            'budgets' => $budgets,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function create(): View
    {
        return view('budgets.create', [
            'customers' => Customer::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'nif']),
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = Budget::create([
            'customer_id' => $request->validated('customer_id'),
            'designation' => $request->validated('designation'),
            'status' => Budget::STATUS_DRAFT,
            'budget_date' => $request->validated('budget_date'),
            'zone' => $request->validated('zone'),
            'project_name' => $request->validated('project_name'),
            'notes' => $request->validated('notes'),
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', "Orçamento {$budget->code} criado em rascunho.");
    }

    public function update(UpdateBudgetHeaderRequest $request, Budget $budget): RedirectResponse
    {
        if (! $budget->isEditable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors([
                    'budget' => 'Só é possível editar o cabeçalho em rascunho.',
                ]);
        }

        $budget->update([
            'designation' => $request->validated('designation'),
            'budget_date' => $request->validated('budget_date'),
            'zone' => $request->validated('zone'),
            'project_name' => $request->validated('project_name'),
            'notes' => $request->validated('notes'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Cabeçalho do orçamento atualizado com sucesso.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        abort_unless(auth()->user()?->can('budgets.delete'), 403);

        if (! $budget->isDeletable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors([
                    'budget' => 'Só é possível apagar orçamentos em rascunho.',
                ]);
        }

        $code = $budget->code;
        $budget->delete();

        return redirect()
            ->route('budgets.index')
            ->with('success', "Orçamento {$code} apagado com sucesso.");
    }

    public function show(Budget $budget): View
    {
        $budget->load([
            'customer',
            'creator',
            'updater',
            'items.item',
            'items.taxRate',
        ]);

        $availableItems = Item::query()
            ->with(['taxRate', 'unit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'type',
                'description',
                'unit_id',
                'tax_rate_id',
                'sale_price',
                'is_active',
            ]);

        $taxRates = TaxRate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'percent',
                'is_exempt',
                'exemption_reason_id',
            ]);

        $taxExemptionReasons = TaxExemptionReason::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'description',
                'invoice_note',
                'legal_reference',
            ]);

        return view('budgets.show', compact(
            'budget',
            'availableItems',
            'taxRates',
            'taxExemptionReasons'
        ));
    }

   public function pdf(Budget $budget)
{
    $budget->load([
        'customer',
        'creator',
        'owner.companyProfile',
        'items.item.unit',
    ]);

    $pdf = Pdf::loadView('budgets.pdf', [
        'budget' => $budget,
    ])->setPaper('a4', 'portrait');

    return $pdf->download($budget->code . '.pdf');
}

    public function changeStatus(
        Request $request,
        Budget $budget,
        ChangeBudgetStatusAction $changeBudgetStatusAction
    ): RedirectResponse {
        abort_unless($request->user()?->can('budgets.update'), 403);

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Budget::statuses())],
        ]);

        $newStatus = (string) $request->input('status');

        try {
            $changeBudgetStatusAction->execute($budget, $newStatus);

            return redirect()
                ->route('budgets.show', $budget)
                ->with('success', 'Estado do orçamento atualizado com sucesso.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors([
                    'budget_status' => $exception->getMessage(),
                ]);
        }
    }
}

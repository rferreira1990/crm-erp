<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\ChangeBudgetStatusAction;
use App\Http\Requests\Budgets\StoreBudgetRequest;
use App\Http\Requests\Budgets\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Item;
use App\Models\TaxExemptionReason;
use App\Models\TaxRate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BudgetController extends Controller
{
    /**
     * Lista de orçamentos.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = (string) $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $allowedStatuses = ['draft', 'sent', 'approved', 'rejected'];

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
            ->when(!empty($dateFrom), function ($query) use ($dateFrom) {
                $query->whereDate('budget_date', '>=', $dateFrom);
            })
            ->when(!empty($dateTo), function ($query) use ($dateTo) {
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

    /**
     * Formulário de criação de orçamento.
     */
    public function create(): View
    {
        return view('budgets.create', [
            'budget' => new Budget(),
            'customers' => $this->getCustomersForSelect(),
        ]);
    }

    /**
     * Guarda um novo orçamento.
     */
    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = Budget::create([
            'customer_id' => $request->validated('customer_id'),
            'designation' => $request->validated('designation'),
            'status' => $request->validated('status'),
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
            ->with('success', "Orçamento {$budget->code} criado com sucesso.");
    }

    /**
     * Formulário de edição do cabeçalho do orçamento.
     */
    public function edit(Budget $budget): View|RedirectResponse
    {
        if (! $budget->isEditable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors([
                    'budget' => 'Só é possível editar o cabeçalho de orçamentos em rascunho.',
                ]);
        }

        return view('budgets.edit', [
            'budget' => $budget,
            'customers' => $this->getCustomersForSelect($budget),
        ]);
    }

    /**
     * Atualiza o cabeçalho do orçamento.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        if (! $budget->isEditable()) {
            return redirect()
                ->route('budgets.show', $budget)
                ->withErrors([
                    'budget' => 'Só é possível editar o cabeçalho de orçamentos em rascunho.',
                ]);
        }

        $budget->update([
            'customer_id' => $request->validated('customer_id'),
            'designation' => $request->validated('designation'),
            'status' => $request->validated('status'),
            'budget_date' => $request->validated('budget_date'),
            'zone' => $request->validated('zone'),
            'project_name' => $request->validated('project_name'),
            'notes' => $request->validated('notes'),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Orçamento atualizado com sucesso.');
    }

    /**
     * Mostra um orçamento e respetivas linhas.
     */
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

    /**
     * Altera o estado do orçamento.
     */
    public function changeStatus(
        Request $request,
        Budget $budget,
        ChangeBudgetStatusAction $changeBudgetStatusAction
    ): RedirectResponse {
        $this->authorizeStatusChange($request);

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

    /**
     * Validação simples do pedido de mudança de estado.
     */
    protected function authorizeStatusChange(Request $request): void
    {
        abort_unless(
            $request->user()?->can('budgets.update'),
            403
        );

        $request->validate([
            'status' => ['required', 'string', 'in:draft,sent,approved,rejected'],
        ]);
    }

    /**
     * Clientes disponíveis para os selects.
     */
    private function getCustomersForSelect(?Budget $budget = null)
    {
        return Customer::query()
            ->where(function ($query) use ($budget) {
                $query->where('is_active', true);

                if ($budget?->customer_id) {
                    $query->orWhere('id', $budget->customer_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'nif']);
    }
}

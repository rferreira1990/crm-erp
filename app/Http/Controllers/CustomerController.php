<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerAccountEntry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerController extends Controller
{
   public function index(Request $request): View
    {
        $this->ensurePermission('customers.view');

        $ownerId = (int) Auth::id();

        $search = trim((string) $request->get('search'));
        $status = $request->get('status');
        $type = $request->get('type');
        $active = $request->get('active');

        $customers = Customer::query()
            ->where(function ($query) use ($ownerId) {
                $query
                    ->where('owner_id', $ownerId)
                    ->orWhere(function ($subQuery) use ($ownerId) {
                        $subQuery
                            ->whereNull('owner_id')
                            ->where('created_by', $ownerId);
                    });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('nif', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->when($active !== null && $active !== '', fn ($query) => $query->where('is_active', (bool) $active))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search', 'status', 'type', 'active'));
    }

    public function create(): View
    {
        $this->ensurePermission('customers.create');

        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->ensurePermission('customers.create');

        $customer = Customer::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Cliente criado com sucesso.');
    }

    public function show(Request $request, Customer $customer): View
    {
        $this->ensurePermission('customers.view');

        $ownerId = (int) Auth::id();
        $customer = $this->resolveOwnedCustomer($customer, $ownerId);

        $filters = [
            'account_date_from' => trim((string) $request->input('account_date_from', '')),
            'account_date_to' => trim((string) $request->input('account_date_to', '')),
        ];

        $entries = CustomerAccountEntry::query()
            ->forOwner($ownerId)
            ->where('customer_id', $customer->id)
            ->with('user:id,name')
            ->when($filters['account_date_from'] !== '', function ($query) use ($filters) {
                $query->whereDate('entry_date', '>=', $filters['account_date_from']);
            })
            ->when($filters['account_date_to'] !== '', function ($query) use ($filters) {
                $query->whereDate('entry_date', '<=', $filters['account_date_to']);
            })
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $runningBalance = 0.0;
        foreach ($entries as $entry) {
            $signedAmount = $entry->signedAmount();
            $runningBalance += $signedAmount;

            $entry->setAttribute('debit_amount', $signedAmount > 0 ? $signedAmount : 0.0);
            $entry->setAttribute('credit_amount', $signedAmount < 0 ? abs($signedAmount) : 0.0);
            $entry->setAttribute('running_balance', $runningBalance);
        }

        $totalDebit = (float) $entries->sum(fn (CustomerAccountEntry $entry): float => (float) ($entry->debit_amount ?? 0));
        $totalCredit = (float) $entries->sum(fn (CustomerAccountEntry $entry): float => (float) ($entry->credit_amount ?? 0));
        $balance = round($totalDebit - $totalCredit, 2);
        $overdueBalance = round(
            (float) $entries
                ->filter(function (CustomerAccountEntry $entry): bool {
                    return $entry->due_date !== null
                        && $entry->due_date->isBefore(Carbon::today())
                        && $entry->signedAmount() > 0;
                })
                ->sum(fn (CustomerAccountEntry $entry): float => $entry->signedAmount()),
            2
        );

        return view('customers.show', [
            'customer' => $customer,
            'accountEntries' => $entries,
            'accountTotals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'balance' => $balance,
                'overdue' => max(0, $overdueBalance),
            ],
            'accountFilters' => $filters,
            'accountEntryTypes' => CustomerAccountEntry::types(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->ensurePermission('customers.edit');

        $this->resolveOwnedCustomer($customer, (int) Auth::id());

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->ensurePermission('customers.edit');

        $customer = $this->resolveOwnedCustomer($customer, (int) Auth::id());

        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->ensurePermission('customers.delete');

        $customer = $this->resolveOwnedCustomer($customer, (int) Auth::id());

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente removido com sucesso.');
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    private function resolveOwnedCustomer(Customer $customer, int $ownerId): Customer
    {
        $customerOwnerId = $customer->owner_id !== null
            ? (int) $customer->owner_id
            : null;

        if ($customerOwnerId === null) {
            $createdBy = $customer->created_by !== null
                ? (int) $customer->created_by
                : null;

            abort_unless($createdBy === $ownerId, 404);

            $customer->forceFill(['owner_id' => $ownerId])->saveQuietly();

            return $customer->refresh();
        }

        abort_unless($customerOwnerId === $ownerId, 404);

        return $customer;
    }
}

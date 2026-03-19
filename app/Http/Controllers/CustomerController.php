<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Lista paginada de clientes.
     */
    public function index(): View
    {
        $customers = Customer::query()
            ->latest()
            ->paginate(15);

        return view('customers.index', compact('customers'));
    }

    /**
     * Formulário de criação de cliente.
     */
    public function create(): View
    {
        return view('customers.create');
    }

    /**
     * Guarda um novo cliente.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente criado com sucesso.');
    }
}

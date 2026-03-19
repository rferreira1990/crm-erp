<?php

namespace App\Http\Controllers;
use App\Models\Budget;
use App\Models\Customer;


use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
{
    $budgets = Budget::with('customer')
        ->latest()
        ->paginate(15);

    return view('budgets.index', compact('budgets'));
}

public function create()
{
    $customers = Customer::orderBy('name')->get();

    return view('budgets.create', compact('customers'));
}
}

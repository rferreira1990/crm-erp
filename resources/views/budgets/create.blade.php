@extends('layouts.admin')

@section('title', 'Novo Orçamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Novo Orçamento</h2>

        <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            Existem erros no formulário. Verifica os campos e tenta novamente.
        </div>
    @endif

    <form method="POST" action="{{ route('budgets.store') }}">
        @csrf

        @include('budgets.partials.form', [
            'budget' => $budget,
            'customers' => $customers,
            'isEdit' => false,
        ])
    </form>
@endsection

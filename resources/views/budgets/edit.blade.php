@extends('layouts.admin')

@section('title', 'Editar Orçamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-0">Editar Orçamento</h2>
            <div class="text-muted">
                {{ $budget->code }}
            </div>
        </div>

        <a href="{{ route('budgets.show', $budget) }}" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            Existem erros no formulário. Verifica os campos e tenta novamente.
        </div>
    @endif

    <form method="POST" action="{{ route('budgets.update', $budget) }}">
        @csrf
        @method('PUT')

        @include('budgets.partials.form', [
            'budget' => $budget,
            'customers' => $customers,
            'isEdit' => true,
        ])
    </form>
@endsection

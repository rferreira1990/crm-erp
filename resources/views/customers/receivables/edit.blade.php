@extends('layouts.admin')

@section('title', 'Editar Conta a Receber')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Editar {{ $receivable->document_number }}</h2>
        <div class="small text-muted">Estado atual: {{ $receivable->statusLabel() }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('customer-receivables.show', $receivable) }}" class="btn btn-outline-primary">Ver detalhe</a>
        <a href="{{ route('customer-receivables.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="card">
    <header class="card-header">
        <h3 class="card-title mb-0">Atualizar documento</h3>
    </header>

    <div class="card-body">
        <form method="POST" action="{{ route('customer-receivables.update', $receivable) }}">
            @csrf
            @method('PUT')

            @include('customers.receivables.partials.form', [
                'receivable' => $receivable,
                'customers' => $customers,
            ])

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Guardar alteracoes</button>
            </div>
        </form>
    </div>
</section>
@endsection

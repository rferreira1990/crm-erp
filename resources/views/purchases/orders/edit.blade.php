@extends('layouts.admin')

@section('title', 'Editar Encomenda Direta')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Editar Encomenda #{{ $order->id }}</h2>
        <div class="small text-muted">Origem: {{ $order->sourceLabel() }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-outline-secondary">Voltar ao detalhe</a>
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
        <h3 class="card-title mb-0">Dados da encomenda</h3>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('purchase-orders.update', $order) }}">
            @csrf
            @method('PUT')

            @include('purchases.orders.partials.form', [
                'order' => $order,
                'suppliers' => $suppliers,
                'paymentTerms' => $paymentTerms,
                'orderItemInitialOptions' => $orderItemInitialOptions,
            ])

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Guardar alteracoes</button>
            </div>
        </form>
    </div>
</section>
@endsection

@extends('layouts.admin')

@section('title', 'Nova Encomenda Direta')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Nova Encomenda Direta</h2>
        <div class="small text-muted">Compras sem RFQ/adjudicacao</div>
    </div>

    <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Voltar</a>
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
        <form method="POST" action="{{ route('purchase-orders.store') }}">
            @csrf

            @include('purchases.orders.partials.form', [
                'order' => $order,
                'suppliers' => $suppliers,
                'paymentTerms' => $paymentTerms,
                'orderItemInitialOptions' => $orderItemInitialOptions,
            ])

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Guardar encomenda</button>
            </div>
        </form>
    </div>
</section>
@endsection

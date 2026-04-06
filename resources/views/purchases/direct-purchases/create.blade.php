@extends('layouts.admin')

@section('title', 'Registar Compra Direta')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Registar Compra Direta</h2>
        <div class="small text-muted">Compra sem encomenda previa com entrada imediata em stock</div>
    </div>

    <a href="{{ route('purchase-direct-purchases.index') }}" class="btn btn-outline-secondary">Voltar</a>
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
        <h3 class="card-title mb-0">Dados da compra</h3>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('purchase-direct-purchases.store') }}" enctype="multipart/form-data">
            @csrf

            @include('purchases.direct-purchases.partials.form', [
                'directPurchase' => $directPurchase,
                'suppliers' => $suppliers,
                'taxRates' => $taxRates,
                'itemInitialOptions' => $itemInitialOptions,
                'paymentMethods' => $paymentMethods,
            ])

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Guardar compra</button>
            </div>
        </form>
    </div>
</section>
@endsection

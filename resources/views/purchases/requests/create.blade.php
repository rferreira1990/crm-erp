@extends('layouts.admin')

@section('title', 'Novo Pedido de Cotacao')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Novo Pedido de Cotacao (RFQ)</h2>
    <a href="{{ route('purchase-requests.index') }}" class="btn btn-light border">Voltar</a>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('purchase-requests.store') }}" method="POST">
            @csrf

            @include('purchases.requests.partials.form')

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="{{ route('purchase-requests.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar RFQ</button>
            </div>
        </form>
    </div>
</div>
@endsection


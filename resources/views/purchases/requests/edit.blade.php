@extends('layouts.admin')

@section('title', 'Editar Pedido de Cotacao')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Editar RFQ {{ $purchaseRequest->code }}</h2>
    <a href="{{ route('purchase-requests.show', $purchaseRequest) }}" class="btn btn-light border">Voltar</a>
</div>

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('purchase-requests.update', $purchaseRequest) }}" method="POST">
            @csrf
            @method('PUT')

            @include('purchases.requests.partials.form')

            <div class="mt-3 d-flex justify-content-end gap-2">
                <a href="{{ route('purchase-requests.show', $purchaseRequest) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Atualizar RFQ</button>
            </div>
        </form>
    </div>
</div>
@endsection


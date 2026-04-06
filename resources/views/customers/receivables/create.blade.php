@extends('layouts.admin')

@section('title', 'Nova Conta a Receber')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Nova Conta a Receber</h2>
        <div class="small text-muted">Documento financeiro operacional interno para gerar debito de cliente</div>
    </div>

    <a href="{{ route('customer-receivables.index') }}" class="btn btn-outline-secondary">Voltar</a>
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
        <h3 class="card-title mb-0">Dados do documento</h3>
    </header>

    <div class="card-body">
        <form method="POST" action="{{ route('customer-receivables.store') }}">
            @csrf

            @include('customers.receivables.partials.form', [
                'receivable' => $receivable,
                'customers' => $customers,
                'creatableStatuses' => $creatableStatuses,
            ])

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Guardar documento</button>
            </div>
        </form>
    </div>
</section>
@endsection

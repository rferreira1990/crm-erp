@extends('layouts.admin')

@section('title', 'Editar Fornecedor')

@section('content')
<div class="row">
    <div class="col">
        <section class="card shadow-sm">
            <header class="card-header">
                <h2 class="card-title mb-0">Editar Fornecedor</h2>
            </header>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Existem erros no formulario.</strong>
                    </div>
                @endif

                <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @include('suppliers.partials.form', [
                        'supplier' => $supplier,
                        'paymentTerms' => $paymentTerms,
                        'taxRates' => $taxRates,
                    ])

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection


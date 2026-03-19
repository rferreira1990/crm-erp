@extends('layouts.admin')

@section('title', 'Novo Orçamento')

@section('content')
<header class="page-header">
    <h2>Novo Orçamento</h2>
</header>

<div class="row">
    <div class="col-lg-8">

        <form action="{{ route('budgets.store') }}" method="POST">
            @csrf

            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Dados do Orçamento</h2>
                </header>

                <div class="card-body">

                    {{-- Cliente --}}
                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Selecionar cliente</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    @selected(old('customer_id') == $customer->id)>
                                    {{ $customer->name }} ({{ $customer->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Estado --}}
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="draft">Rascunho</option>
                            <option value="sent">Enviado</option>
                            <option value="approved">Aprovado</option>
                            <option value="rejected">Rejeitado</option>
                        </select>
                    </div>

                    {{-- Notas --}}
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
                    </div>

                </div>

                <footer class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        Guardar
                    </button>

                    <a href="{{ route('budgets.index') }}" class="btn btn-light border">
                        Cancelar
                    </a>
                </footer>
            </section>
        </form>

    </div>
</div>
@endsection

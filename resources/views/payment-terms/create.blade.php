@extends('layouts.admin')

@section('title', 'Nova Condição de Pagamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Nova Condição de Pagamento</h2>

        <a href="{{ route('payment-terms.index') }}" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">
                Não foi possível guardar. Corrige os seguintes erros:
            </div>

            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('payment-terms.store') }}">
        @csrf

        <div class="row">
            <div class="col-xl-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Dados da condição de pagamento</strong>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label">Nome *</label>
                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    maxlength="150"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label for="days" class="form-label">Dias</label>
                                <input
                                    type="number"
                                    name="days"
                                    id="days"
                                    class="form-control @error('days') is-invalid @enderror"
                                    value="{{ old('days') }}"
                                    min="0"
                                    max="9999"
                                >
                            </div>

                            <div class="col-md-4">
                                <label for="sort_order" class="form-label">Ordem</label>
                                <input
                                    type="number"
                                    name="sort_order"
                                    id="sort_order"
                                    class="form-control @error('sort_order') is-invalid @enderror"
                                    value="{{ old('sort_order', 0) }}"
                                    min="0"
                                    max="9999"
                                >
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="1"
                                        id="is_active"
                                        name="is_active"
                                        {{ old('is_active', '1') ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="is_active">
                                        Ativa
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

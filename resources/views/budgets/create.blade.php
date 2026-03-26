@extends('layouts.admin')

@section('title', 'Novo Orçamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Novo Orçamento</h2>

        <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">
                Não foi possível guardar o orçamento. Corrige os seguintes erros:
            </div>

            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('budgets.store') }}">
        @csrf

        <div class="row">
            <div class="col-xl-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Dados do orçamento</strong>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-12">
                                <label for="customer_search" class="form-label">Cliente *</label>

                                <div class="position-relative">
                                    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">

                                    <input
                                        type="text"
                                        id="customer_search"
                                        class="form-control @error('customer_id') is-invalid @enderror"
                                        placeholder="Pesquisar por nome, ID, código ou NIF"
                                        autocomplete="off"
                                    >

                                    <div id="customer_dropdown"
                                         class="list-group position-absolute w-100 shadow-sm d-none"
                                         style="z-index: 1050; max-height: 260px; overflow-y: auto;">
                                    </div>
                                </div>

                                @error('customer_id')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="form-text">
                                    O orçamento é criado sempre em rascunho.
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Designação</label>
                                <input type="text" name="designation" class="form-control" value="{{ old('designation') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Data *</label>
                                <input type="date" name="budget_date" class="form-control" value="{{ now()->toDateString() }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Zona</label>
                                <input type="text" name="zone" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Projeto</label>
                                <input type="text" name="project_name" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" rows="4" class="form-control"></textarea>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            Criar orçamento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

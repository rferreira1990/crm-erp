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
            Existem erros no formulário. Verifica os campos e tenta novamente.
        </div>
    @endif

    <form method="POST" action="{{ route('budgets.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Dados do orçamento</strong>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Cliente *</label>

                            <select
                                name="customer_id"
                                id="customer_id"
                                class="form-select @error('customer_id') is-invalid @enderror"
                                required
                            >
                                <option value="">Selecionar cliente</option>

                                @foreach ($customers as $customer)
                                    <option
                                        value="{{ $customer->id }}"
                                        {{ old('customer_id') == $customer->id ? 'selected' : '' }}
                                    >
                                        {{ $customer->code }} - {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('customer_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notas</label>

                            <textarea
                                name="notes"
                                id="notes"
                                rows="4"
                                class="form-control @error('notes') is-invalid @enderror"
                            >{{ old('notes') }}</textarea>

                            @error('notes')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Configuração</strong>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Estado *</label>

                            <select
                                name="status"
                                id="status"
                                class="form-select @error('status') is-invalid @enderror"
                                required
                            >
                                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>
                                    Rascunho
                                </option>
                                <option value="sent" {{ old('status') === 'sent' ? 'selected' : '' }}>
                                    Enviado
                                </option>
                                <option value="approved" {{ old('status') === 'approved' ? 'selected' : '' }}>
                                    Aprovado
                                </option>
                                <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>
                                    Rejeitado
                                </option>
                            </select>

                            @error('status')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Criar Orçamento
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

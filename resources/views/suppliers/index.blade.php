@extends('layouts.admin')

@section('title', 'Fornecedores')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Fornecedores</h2>
    </div>

    @can('suppliers.create')
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            Novo Fornecedor
        </a>
    @endcan
</div>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('suppliers.index') }}" method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Codigo, nome, NIF, email, telefone ou contacto"
                    >
                </div>

                <div class="col-md-3">
                    <label for="is_active" class="form-label">Ativo</label>
                    <select name="is_active" id="is_active" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" {{ (string) ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Sim</option>
                        <option value="0" {{ (string) ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Nao</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary w-100">Limpar</a>
                </div>
            </div>
        </form>

        @if ($suppliers->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Fornecedor</th>
                            <th>NIF</th>
                            <th>Contacto principal</th>
                            <th>Condicao pagamento</th>
                            <th>Lead time</th>
                            <th>Ativo</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            @php
                                $primaryContact = $supplier->primaryContact;
                            @endphp

                            <tr>
                                <td>{{ $supplier->code }}</td>
                                <td>
                                    <a href="{{ route('suppliers.show', $supplier) }}">
                                        <strong>{{ $supplier->name }}</strong>
                                    </a>
                                    @if (!empty($supplier->external_reference))
                                        <div class="small text-muted">Ref: {{ $supplier->external_reference }}</div>
                                    @endif
                                </td>
                                <td>{{ $supplier->tax_number ?: '-' }}</td>
                                <td>
                                    @if ($primaryContact)
                                        <div>{{ $primaryContact->name }}</div>
                                        <div class="small text-muted">
                                            {{ $primaryContact->email ?: ($primaryContact->phone ?: ($primaryContact->mobile ?: '-')) }}
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($supplier->paymentTerm)
                                        {{ $supplier->paymentTerm->displayLabel() }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $supplier->lead_time_days !== null ? $supplier->lead_time_days . ' dias' : '-' }}</td>
                                <td>
                                    @if ($supplier->is_active)
                                        <span class="badge bg-success">Sim</span>
                                    @else
                                        <span class="badge bg-secondary">Nao</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>

                                    @can('suppliers.update')
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-primary">
                                            Editar
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $suppliers->links() }}
            </div>
        @else
            <div class="text-muted">Nao foram encontrados fornecedores com os filtros aplicados.</div>
        @endif
    </div>
</div>
@endsection


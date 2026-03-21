@extends('layouts.admin')

@section('title', 'Orçamentos')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Orçamentos</h2>

        @can('budgets.create')
            <a href="{{ route('budgets.create') }}" class="btn btn-primary">
                Novo Orçamento
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            @if ($budgets->count())
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end">Total</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($budgets as $budget)
                                <tr>
                                    <td>
                                        <a href="{{ route('budgets.show', $budget) }}">
                                            <strong>{{ $budget->code }}</strong>
                                        </a>
                                    </td>

                                    <td>
                                        {{ $budget->customer->name ?? '—' }}
                                    </td>

                                    <td>
                                        @if ($budget->status === 'draft')
                                            <span class="badge bg-secondary">Rascunho</span>
                                        @elseif ($budget->status === 'sent')
                                            <span class="badge bg-info">Enviado</span>
                                        @elseif ($budget->status === 'approved')
                                            <span class="badge bg-success">Aprovado</span>
                                        @elseif ($budget->status === 'rejected')
                                            <span class="badge bg-danger">Rejeitado</span>
                                        @else
                                            <span class="badge bg-dark">{{ $budget->status }}</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        {{ number_format((float) $budget->subtotal, 2, ',', '.') }} €
                                    </td>

                                    <td class="text-end">
                                        {{ number_format((float) $budget->tax_total, 2, ',', '.') }} €
                                    </td>

                                    <td class="text-end">
                                        <strong>
                                            {{ number_format((float) $budget->total, 2, ',', '.') }} €
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $budget->created_at?->format('d/m/Y') }}
                                    </td>

                                    <td>
                                        <a href="{{ route('budgets.show', $budget) }}" class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $budgets->links() }}
                </div>
            @else
                <div class="text-muted">
                    Ainda não existem orçamentos.
                </div>
            @endif
        </div>
    </div>
@endsection

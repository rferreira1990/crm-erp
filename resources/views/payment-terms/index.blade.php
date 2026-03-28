@extends('layouts.admin')

@section('title', 'Condicoes de Pagamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Condicoes de Pagamento</h2>

        <a href="{{ route('payment-terms.create') }}" class="btn btn-primary">
            Nova condicao
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Dias</th>
                            <th>Ativa</th>
                            <th>Origem</th>
                            <th>Ordem</th>
                            <th style="width: 180px;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentTerms as $paymentTerm)
                            <tr>
                                <td>{{ $paymentTerm->name }}</td>
                                <td>{{ $paymentTerm->days !== null ? $paymentTerm->days : '-' }}</td>
                                <td>
                                    @if ($paymentTerm->is_active)
                                        <span class="badge bg-success">Sim</span>
                                    @else
                                        <span class="badge bg-secondary">Nao</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">Empresa</span>
                                </td>
                                <td>{{ $paymentTerm->sort_order }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('payment-terms.edit', $paymentTerm) }}" class="btn btn-sm btn-outline-primary">
                                            Editar
                                        </a>

                                        <form method="POST" action="{{ route('payment-terms.destroy', $paymentTerm) }}" onsubmit="return confirm('Apagar esta condicao de pagamento?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Apagar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Ainda nao existem condicoes de pagamento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

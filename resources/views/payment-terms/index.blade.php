@extends('layouts.admin')

@section('title', 'Condições de Pagamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Condições de Pagamento</h2>

        <a href="{{ route('payment-terms.create') }}" class="btn btn-primary">
            Nova condição
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
                            <th style="width: 180px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentTerms as $paymentTerm)
                            <tr>
                                <td>{{ $paymentTerm->name }}</td>
                                <td>{{ $paymentTerm->days !== null ? $paymentTerm->days : '—' }}</td>
                                <td>
                                    @if ($paymentTerm->is_active)
                                        <span class="badge bg-success">Sim</span>
                                    @else
                                        <span class="badge bg-secondary">Não</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($paymentTerm->owner_id === null)
                                        <span class="badge bg-info text-dark">Sistema</span>
                                    @else
                                        <span class="badge bg-primary">Personalizada</span>
                                    @endif
                                </td>
                                <td>{{ $paymentTerm->sort_order }}</td>
                                <td>
                                    @if ($paymentTerm->owner_id !== null)
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('payment-terms.edit', $paymentTerm) }}" class="btn btn-sm btn-outline-primary">
                                                Editar
                                            </a>

                                            <form method="POST" action="{{ route('payment-terms.destroy', $paymentTerm) }}" onsubmit="return confirm('Apagar esta condição de pagamento?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Apagar
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-muted">Default do sistema</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Ainda não existem condições de pagamento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Orçamentos')

@section('content')
<header class="page-header">
    <h2>Orçamentos</h2>
</header>

<div class="row">
    <div class="col">
        <section class="card">

            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Lista de Orçamentos</h2>

                <a href="{{ route('budgets.create') }}" class="btn btn-primary btn-sm">
                    Novo Orçamento
                </a>
            </header>

            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-ecommerce-simple mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Data</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($budgets as $budget)
                                <tr>
                                    <td>{{ $budget->code }}</td>

                                    <td>
                                        {{ $budget->customer->name ?? '-' }}
                                    </td>

                                    <td>
                                        @if($budget->status === 'draft')
                                            <span class="badge bg-secondary">Rascunho</span>
                                        @elseif($budget->status === 'sent')
                                            <span class="badge bg-info text-dark">Enviado</span>
                                        @elseif($budget->status === 'approved')
                                            <span class="badge bg-success">Aprovado</span>
                                        @else
                                            <span class="badge bg-danger">Rejeitado</span>
                                        @endif
                                    </td>

                                    <td>{{ number_format($budget->total, 2, ',', '.') }} €</td>

                                    <td>{{ $budget->created_at->format('d/m/Y') }}</td>

                                    <td class="text-end">
                                        <a href="#" class="btn btn-light btn-sm border">
                                            Ver
                                        </a>

                                        <a href="#" class="btn btn-primary btn-sm">
                                            Editar
                                        </a>

                                        <a href="#" class="btn btn-danger btn-sm">
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        Nenhum orçamento encontrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $budgets->links() }}
                </div>

            </div>
        </section>
    </div>
</div>
@endsection

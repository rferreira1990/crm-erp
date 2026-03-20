@extends('layouts.admin')

@section('title', 'Motivos de Isenção')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Motivos de Isenção</h2>

        <a href="{{ route('tax-exemption-reasons.create') }}" class="btn btn-primary btn-sm">
            Novo Motivo
        </a>
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($taxExemptionReasons->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Nota para fatura</th>
                            <th>Referência legal</th>
                            <th>Estado</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($taxExemptionReasons as $reason)
                            <tr>
                                <td>{{ $reason->code }}</td>
                                <td>{{ $reason->description }}</td>
                                <td>{{ $reason->invoice_note ?: '—' }}</td>
                                <td>{{ $reason->legal_reference ?: '—' }}</td>
                                <td>
                                    @if($reason->is_active)
                                        <span class="badge badge-success">Ativo</span>
                                    @else
                                        <span class="badge badge-secondary">Inativo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('tax-exemption-reasons.edit', $reason) }}" class="btn btn-default btn-sm">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $taxExemptionReasons->links() }}
            </div>
        @else
            <p class="mb-0">Ainda não existem motivos de isenção registados.</p>
        @endif
    </div>
</section>
@endsection

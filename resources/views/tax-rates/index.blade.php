@extends('layouts.admin')

@section('title', 'Taxas de IVA')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Taxas de IVA</h2>

        <a href="{{ route('tax-rates.create') }}" class="btn btn-primary btn-sm">
            Nova Taxa
        </a>
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($taxRates->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>%</th>
                            <th>SAF-T</th>
                            <th>País</th>
                            <th>Isenta</th>
                            <th>Default</th>
                            <th>Estado</th>
                            <th>Ordem</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($taxRates as $taxRate)
                            <tr>
                                <td>{{ $taxRate->name }}</td>
                                <td>{{ number_format((float) $taxRate->percent, 2, ',', '.') }}</td>
                                <td>{{ $taxRate->saft_code }}</td>
                                <td>{{ $taxRate->country_code }}</td>
                                <td>
                                    @if($taxRate->is_exempt)
                                        <span class="badge badge-info">Sim</span>
                                    @else
                                        <span class="badge badge-default">Não</span>
                                    @endif
                                </td>
                                <td>
                                    @if($taxRate->is_default)
                                        <span class="badge badge-primary">Sim</span>
                                    @else
                                        <span class="badge badge-default">Não</span>
                                    @endif
                                </td>
                                <td>
                                    @if($taxRate->is_active)
                                        <span class="badge badge-success">Ativa</span>
                                    @else
                                        <span class="badge badge-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td>{{ $taxRate->sort_order }}</td>
                                <td class="text-end">
                                    <a href="{{ route('tax-rates.edit', $taxRate) }}" class="btn btn-default btn-sm">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $taxRates->links() }}
            </div>
        @else
            <p class="mb-0">Ainda não existem taxas registadas.</p>
        @endif
    </div>
</section>
@endsection

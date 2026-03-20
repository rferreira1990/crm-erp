@extends('layouts.admin')

@section('title', 'Artigos')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Artigos / Serviços</h2>

        @can('items.create')
            <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm">
                Novo Artigo
            </a>
        @endcan
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($items->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Família</th>
                            <th>Marca</th>
                            <th>Unidade</th>
                            <th>IVA</th>
                            <th>P. Venda</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>{{ $item->code }}</td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    @if($item->type === 'service')
                                        <span class="badge badge-info">Serviço</span>
                                    @else
                                        <span class="badge badge-primary">Produto</span>
                                    @endif
                                </td>
                                <td>{{ $item->family->name ?? '—' }}</td>
                                <td>{{ $item->brand->name ?? '—' }}</td>
                                <td>{{ $item->unit ? $item->unit->code . ' - ' . $item->unit->name : '—' }}</td>
                                <td>
                                    @if($item->taxRate)
                                        {{ $item->taxRate->saft_code }}
                                        ({{ number_format((float) $item->taxRate->percent, 2, ',', '.') }}%)
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ number_format((float) $item->sale_price, 2, ',', '.') }}</td>
                                <td>
                                    @if($item->is_active)
                                        <span class="badge badge-success">Ativo</span>
                                    @else
                                        <span class="badge badge-secondary">Inativo</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $items->links() }}
            </div>
        @else
            <p class="mb-0">Ainda não existem artigos registados.</p>
        @endif
    </div>
</section>
@endsection

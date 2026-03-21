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
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        <form action="{{ route('items.index') }}" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        value="{{ $filters['search'] }}"
                        placeholder="Nome, código ou código de barras"
                    >
                </div>

                <div class="col-md-2 mb-3">
                    <label for="type" class="form-label">Tipo</label>
                    <select name="type" id="type" class="form-control">
                        <option value="">Todos</option>
                        <option value="product" {{ $filters['type'] === 'product' ? 'selected' : '' }}>Produto</option>
                        <option value="service" {{ $filters['type'] === 'service' ? 'selected' : '' }}>Serviço</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label for="status" class="form-label">Estado</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Ativo</option>
                        <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Inativo</option>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label for="family_id" class="form-label">Família</label>
                    <select name="family_id" id="family_id" class="form-control">
                        <option value="">Todas</option>
                        @foreach($families as $family)
                            <option value="{{ $family->id }}" {{ (string) $filters['family_id'] === (string) $family->id ? 'selected' : '' }}>
                                {{ $family->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label for="brand_id" class="form-label">Marca</label>
                    <select name="brand_id" id="brand_id" class="form-control">
                        <option value="">Todas</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" {{ (string) $filters['brand_id'] === (string) $brand->id ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    Filtrar
                </button>

                <a href="{{ route('items.index') }}" class="btn btn-light btn-sm">
                    Limpar filtros
                </a>
            </div>
        </form>

        @if($items->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Família</th>
                            <th>Marca</th>
                            <th>Unidade</th>
                            <th>IVA</th>
                            <th>P. Venda</th>
                            <th>Estado</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td style="width: 90px;">
                                    @if($item->primaryImage)
                                        <img
                                            src="{{ $item->primaryImage->url }}"
                                            alt="{{ $item->name }}"
                                            style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                        >
                                    @else
                                        <div class="text-muted small">Sem foto</div>
                                    @endif
                                </td>

                                <td>{{ $item->code }}</td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    @if($item->type === 'service')
                                        <span class="badge bg-info">Serviço</span>
                                    @else
                                        <span class="badge bg-primary">Produto</span>
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
                                        <span class="badge bg-success">Ativo</span>
                                    @else
                                        <span class="badge bg-secondary">Inativo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        @can('items.view')
                                            <a href="{{ route('items.show', $item) }}"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Ver">
                                                Ver
                                            </a>
                                        @endcan

                                        @can('items.edit')
                                            <a href="{{ route('items.edit', $item) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Editar">
                                                Editar
                                            </a>
                                        @endcan
                                    </div>
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
            <p class="mb-0">Não foram encontrados artigos com os filtros aplicados.</p>
        @endif
    </div>
</section>
@endsection

@extends('layouts.admin')

@section('title', 'Detalhe do Artigo')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="card-title mb-1">{{ $item->name }}</h2>
            <div class="text-muted">
                Código: <strong>{{ $item->code }}</strong>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('items.index') }}" class="btn btn-light btn-sm">
                Voltar
            </a>

            @can('items.edit')
                <a href="{{ route('items.edit', $item) }}" class="btn btn-primary btn-sm">
                    Editar
                </a>
            @endcan
        </div>
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Imagem principal</h3>
                    </div>

                    <div class="card-body text-center">
                        @if($item->primaryImage)
                            <img
                                src="{{ $item->primaryImage->url }}"
                                alt="{{ $item->name }}"
                                class="img-fluid rounded"
                                style="max-height: 320px; object-fit: contain;"
                            >
                        @else
                            <div class="text-muted py-5">
                                Sem imagem principal
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-8 mb-4">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Dados gerais</h3>
                            </div>

                            <div class="card-body">
                                <table class="table table-borderless table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <th style="width: 180px;">Código</th>
                                            <td>{{ $item->code }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nome</th>
                                            <td>{{ $item->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nome curto</th>
                                            <td>{{ $item->short_name ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tipo</th>
                                            <td>
                                                @if($item->type === 'service')
                                                    <span class="badge bg-info">Serviço</span>
                                                @else
                                                    <span class="badge bg-primary">Produto</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Estado</th>
                                            <td>
                                                @if($item->is_active)
                                                    <span class="badge bg-success">Ativo</span>
                                                @else
                                                    <span class="badge bg-secondary">Inativo</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Família</th>
                                            <td>{{ $item->family->name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Marca</th>
                                            <td>{{ $item->brand->name ?? '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Unidade</th>
                                            <td>{{ $item->unit ? $item->unit->code . ' - ' . $item->unit->name : '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>IVA</th>
                                            <td>
                                                @if($item->taxRate)
                                                    {{ $item->taxRate->name }}
                                                    ({{ number_format((float) $item->taxRate->percent, 2, ',', '.') }}%)
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Código de barras</th>
                                            <td>{{ $item->barcode ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Ref. fornecedor</th>
                                            <td>{{ $item->supplier_reference ?: '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Preços e stock</h3>
                            </div>

                            <div class="card-body">
                                <table class="table table-borderless table-sm mb-0">
                                    <tbody>
                                        <tr>
                                            <th style="width: 180px;">Preço de custo</th>
                                            <td>{{ number_format((float) $item->cost_price, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Preço de venda</th>
                                            <td>{{ number_format((float) $item->sale_price, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Desc. máximo</th>
                                            <td>{{ number_format((float) $item->max_discount_percent, 2, ',', '.') }}%</td>
                                        </tr>

                                        @if($item->type === 'product')
                                            <tr>
                                                <th>Controla stock</th>
                                                <td>{{ $item->tracks_stock ? 'Sim' : 'Não' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Stock mínimo</th>
                                                <td>{{ number_format((float) $item->min_stock, 2, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Stock máximo</th>
                                                <td>
                                                    @if($item->max_stock !== null)
                                                        {{ number_format((float) $item->max_stock, 2, ',', '.') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Stock atual</th>
                                                <td>{{ number_format((float) $item->current_stock, 3, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Alerta stock</th>
                                                <td>{{ $item->stock_alert ? 'Sim' : 'Não' }}</td>
                                            </tr>
                                        @else
                                            <tr>
                                                <th>Stock</th>
                                                <td>
                                                    <span class="text-muted">Serviço não controla stock</span>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Descrição</h3>
                    </div>

                    <div class="card-body">
                        @if($item->description)
                            {!! nl2br(e($item->description)) !!}
                        @else
                            <span class="text-muted">Sem descrição.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">Galeria de imagens</h3>
            </div>

            <div class="card-body">
                @if($item->images->count())
                    <div class="row">
                        @foreach($item->images as $image)
                            <div class="col-md-3 mb-4">
                                <div class="card h-100">
                                    <img
                                        src="{{ $image->url }}"
                                        alt="{{ $image->original_name }}"
                                        class="card-img-top"
                                        style="height: 180px; object-fit: cover;"
                                    >

                                    <div class="card-body">
                                        <div class="small text-break mb-2">
                                            {{ $image->original_name }}
                                        </div>

                                        <div class="small text-muted mb-2">
                                            {{ $image->readable_size }}
                                        </div>

                                        @if($image->is_primary)
                                            <span class="badge bg-success">Principal</span>
                                        @endif
                                    </div>

                                    <div class="card-footer bg-white">
                                        <a href="{{ $image->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary w-100">
                                            Ver imagem
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mb-0 text-muted">Este artigo ainda não tem imagens associadas.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Documentos PDF</h3>
            </div>

            <div class="card-body">
                @if($item->documents->count())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Tamanho</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($item->documents as $document)
                                    <tr>
                                        <td>{{ $document->original_name }}</td>
                                        <td>{{ $document->readable_size }}</td>
                                        <td class="text-end">
                                            <a href="{{ $document->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">
                                                Abrir PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mb-0 text-muted">Este artigo ainda não tem documentos PDF associados.</p>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

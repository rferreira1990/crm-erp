@extends('layouts.admin')

@section('title', 'Importar artigos')

@section('content')
<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title mb-0">Importar artigos</h2>
            </header>

            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                @endif

                <p class="mb-3">
                    Importacao por <strong>code</strong>: se o codigo existir, atualiza; se nao existir, cria.
                    Familia e marca podem ser criadas automaticamente. Unidade e IVA tem de existir.
                </p>
                <p class="mb-3 text-muted">
                    Para familias hierarquicas usa o formato: <strong>Familia > Subfamilia > Sub-subfamilia</strong>.
                </p>

                <div class="mb-3 d-flex gap-2">
                    <a href="{{ route('items.import.template') }}" class="btn btn-outline-secondary btn-sm">
                        Descarregar template CSV
                    </a>
                    <a href="{{ route('items.index') }}" class="btn btn-light btn-sm">
                        Voltar
                    </a>
                </div>

                <form action="{{ route('items.import.preview') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                    @csrf

                    <div class="mb-3">
                        <label for="import_file" class="form-label">Ficheiro CSV</label>
                        <input
                            type="file"
                            name="import_file"
                            id="import_file"
                            class="form-control @error('import_file') is-invalid @enderror"
                            accept=".csv,.txt"
                            required
                        >
                        @error('import_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-2">
                            Colunas esperadas (minimo): code, name, unit, tax_rate.
                        </small>
                        <small class="text-muted d-block">
                            Colunas suportadas: type, item_family, brand, purchase_price, sale_price, tracks_stock,
                            min_stock, max_stock, is_active, notes, barcode, supplier_reference, short_name, max_discount_percent.
                        </small>
                        <small class="text-muted d-block">
                            Exemplo de item_family: Tomadas > IP40 > Brancas.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Validar ficheiro
                    </button>
                </form>

                @if(is_array($preview ?? null))
                    @php($summary = $preview['summary'] ?? [])

                    <div class="border rounded p-3 mb-3 bg-light">
                        <h5 class="mb-3">Resumo da validacao</h5>
                        <div class="row">
                            <div class="col-md-2 mb-2"><strong>Total:</strong> {{ $summary['total_rows'] ?? 0 }}</div>
                            <div class="col-md-2 mb-2"><strong>Criar:</strong> {{ $summary['to_create'] ?? 0 }}</div>
                            <div class="col-md-2 mb-2"><strong>Atualizar:</strong> {{ $summary['to_update'] ?? 0 }}</div>
                            <div class="col-md-3 mb-2"><strong>Familias/subfamilias novas:</strong> {{ $summary['families_to_create'] ?? 0 }}</div>
                            <div class="col-md-3 mb-2"><strong>Marcas novas:</strong> {{ $summary['brands_to_create'] ?? 0 }}</div>
                        </div>
                        <div class="mt-2">
                            <strong>Erros:</strong> {{ $summary['errors'] ?? 0 }}
                            @if(!empty($sourceFileName))
                                <span class="text-muted">| Ficheiro: {{ $sourceFileName }}</span>
                            @endif
                        </div>
                    </div>

                    @if(!empty($preview['errors']))
                        <div class="alert alert-danger" role="alert">
                            Foram encontrados erros de validacao. Corrige o ficheiro e volta a tentar.
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Linha</th>
                                        <th>Erros</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($preview['errors'] as $errorRow)
                                        <tr>
                                            <td>{{ $errorRow['line'] ?? '-' }}</td>
                                            <td>{{ implode(' | ', $errorRow['messages'] ?? []) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif(!empty($confirmToken))
                        <div class="alert alert-success" role="alert">
                            Validacao concluida sem erros. Podes confirmar a importacao.
                        </div>

                        <form action="{{ route('items.import.confirm') }}" method="POST">
                            @csrf
                            <input type="hidden" name="import_token" value="{{ $confirmToken }}">

                            <button type="submit" class="btn btn-success">
                                Confirmar importacao
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning mb-0" role="alert">
                            O ficheiro nao tem linhas de dados para importar.
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
@endsection

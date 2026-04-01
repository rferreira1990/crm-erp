@extends('layouts.admin')

@section('title', 'Familias de Artigos')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Familias de Artigos</h2>

        <a href="{{ route('item-families.create') }}" class="btn btn-primary btn-sm">
            Nova Familia
        </a>
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        @if($itemFamilies->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Familia</th>
                            <th>Descricao</th>
                            <th class="text-center">Artigos</th>
                            <th class="text-center">Subfamilias</th>
                            <th>Estado</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($itemFamilies as $itemFamily)
                            <tr>
                                <td>
                                    <div>
                                        {!! str_repeat('&mdash; ', (int) ($itemFamily->depth ?? 0)) !!}{{ $itemFamily->name }}
                                    </div>
                                    @if(($itemFamily->path_label ?? $itemFamily->name) !== $itemFamily->name)
                                        <small class="text-muted">{{ $itemFamily->path_label }}</small>
                                    @endif
                                </td>
                                <td>{{ $itemFamily->description ?: '-' }}</td>
                                <td class="text-center">{{ $itemFamily->items_count ?? 0 }}</td>
                                <td class="text-center">{{ $itemFamily->children_count ?? 0 }}</td>
                                <td>
                                    @if($itemFamily->is_active)
                                        <span class="badge bg-success">Ativa</span>
                                    @else
                                        <span class="badge bg-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('item-families.edit', $itemFamily) }}" class="btn btn-outline-primary btn-sm">
                                            Editar
                                        </a>

                                        <form action="{{ route('item-families.destroy', $itemFamily) }}" method="POST" onsubmit="return confirm('Apagar esta familia?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Apagar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mb-0">Ainda nao existem familias registadas.</p>
        @endif
    </div>
</section>
@endsection

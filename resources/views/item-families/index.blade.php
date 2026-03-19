@extends('layouts.admin')

@section('title', 'Famílias de Artigos')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Famílias de Artigos</h2>

        <a href="{{ route('item-families.create') }}" class="btn btn-primary btn-sm">
            Nova Família
        </a>
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($itemFamilies->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Estado</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($itemFamilies as $itemFamily)
                            <tr>
                                <td>{{ $itemFamily->name }}</td>
                                <td>{{ $itemFamily->description ?: '—' }}</td>
                                <td>
                                    @if($itemFamily->is_active)
                                        <span class="badge badge-success">Ativa</span>
                                    @else
                                        <span class="badge badge-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('item-families.edit', $itemFamily) }}" class="btn btn-default btn-sm">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $itemFamilies->links() }}
            </div>
        @else
            <p class="mb-0">Ainda não existem famílias registadas.</p>
        @endif
    </div>
</section>
@endsection

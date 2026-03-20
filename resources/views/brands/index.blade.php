@extends('layouts.admin')

@section('title', 'Marcas')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Marcas</h2>

        <a href="{{ route('brands.create') }}" class="btn btn-primary btn-sm">
            Nova Marca
        </a>
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($brands->count())
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
                        @foreach($brands as $brand)
                            <tr>
                                <td>{{ $brand->name }}</td>
                                <td>{{ $brand->description ?: '—' }}</td>
                                <td>
                                    @if($brand->is_active)
                                        <span class="badge badge-success">Ativa</span>
                                    @else
                                        <span class="badge badge-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('brands.edit', $brand) }}" class="btn btn-default btn-sm">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $brands->links() }}
            </div>
        @else
            <p class="mb-0">Ainda não existem marcas registadas.</p>
        @endif
    </div>
</section>
@endsection

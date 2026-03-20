@extends('layouts.admin')

@section('title', 'Unidades')

@section('content')
<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Unidades</h2>

        <a href="{{ route('units.create') }}" class="btn btn-primary btn-sm">
            Nova Unidade
        </a>
    </header>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($units->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Fator</th>
                            <th>Estado</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($units as $unit)
                            <tr>
                                <td>{{ $unit->code }}</td>
                                <td>{{ $unit->name }}</td>
                                <td>{{ number_format((float) $unit->factor, 3, ',', '.') }}</td>
                                <td>
                                    @if($unit->is_active)
                                        <span class="badge badge-success">Ativa</span>
                                    @else
                                        <span class="badge badge-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('units.edit', $unit) }}" class="btn btn-default btn-sm">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $units->links() }}
            </div>
        @else
            <p class="mb-0">Ainda não existem unidades registadas.</p>
        @endif
    </div>
</section>
@endsection

@extends('layouts.admin')

@section('title', 'Séries')

@section('content')

<div class="justify-content-between mb-3">
    <h2>Séries</h2>

    <a href="{{ route('document-series.create') }}" class="btn btn-primary">
        Nova Série
    </a>
</div>

<div class="card">
    <div class="card-body">

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Série</th>
                    <th>Prefixo</th>
                    <th>Próximo Nº</th>
                    <th>Ativa</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @foreach($series as $s)
                    <tr>
                        <td>{{ $s->document_type }}</td>
                        <td>{{ $s->name }}</td>
                        <td>{{ $s->prefix }}</td>
                        <td>{{ $s->next_number }}</td>
                        <td>
                            @if($s->is_active)
                                <span class="badge bg-success">Sim</span>
                            @else
                                <span class="badge bg-secondary">Não</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('document-series.edit', $s) }}" class="btn btn-sm btn-outline-primary">
                                Editar
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>

    </div>
</div>

@endsection

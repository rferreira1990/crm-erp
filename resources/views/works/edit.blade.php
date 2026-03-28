@extends('layouts.admin')

@section('title', 'Editar Obra')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Editar Obra</h2>
        <small class="text-muted">{{ $work->code }}</small>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">
            Ver detalhe
        </a>

        <a href="{{ route('works.index') }}" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('works.update', $work) }}" method="POST">
            @csrf
            @method('PUT')

            @include('works.partials.form', [
                'work' => $work,
            ])

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    Atualizar Obra
                </button>

                <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

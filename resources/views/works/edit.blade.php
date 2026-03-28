@extends('layouts.admin')

@section('title', 'Editar Obra')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Editar Obra</h2>
        <div class="text-muted">
            {{ $work->name }} · <strong>{{ $work->code }}</strong>
        </div>
    </div>

    <div>
        <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">
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

<form action="{{ route('works.update', $work) }}" method="POST">
    @csrf
    @method('PUT')

    @include('works.partials.form', [
        'work' => $work,
    ])

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">
            Cancelar
        </a>

        <button type="submit" class="btn btn-primary">
            Atualizar Obra
        </button>
    </div>
</form>
@endsection

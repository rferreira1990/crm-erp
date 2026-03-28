@extends('layouts.admin')

@section('title', 'Nova Obra')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Nova Obra</h2>

    <a href="{{ route('works.index') }}" class="btn btn-outline-secondary">
        Voltar
    </a>
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
        <form action="{{ route('works.store') }}" method="POST">
            @csrf

            @include('works.partials.form', [
                'work' => null,
            ])

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    Guardar Obra
                </button>

                <a href="{{ route('works.index') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

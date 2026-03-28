@extends('layouts.admin')

@section('title', 'Editar Utilizador')

@section('content')
<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title mb-0">Editar Utilizador</h2>
            </header>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Existem erros no formulario.</strong>
                    </div>
                @endif

                <form action="{{ route('users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @include('users.partials.form')

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('users.show', $user) }}" class="btn btn-light border">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Novo Utilizador')

@section('content')
<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title mb-0">Novo Utilizador</h2>
            </header>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Existem erros no formulario.</strong>
                    </div>
                @endif

                <form action="{{ route('users.store') }}" method="POST">
                    @csrf

                    @include('users.partials.form')

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('users.index') }}" class="btn btn-light border">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

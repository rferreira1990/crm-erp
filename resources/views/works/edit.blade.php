@extends('layouts.admin')

@section('title', 'Nova Obra')

@section('content')
    <div class="row">
        <div class="col">
            <section class="card card-modern">
                <div class="card-header">
                    <div class="card-actions">
                        <a href="{{ route('works.index') }}" class="btn btn-light btn-sm">
                            Voltar
                        </a>
                    </div>
                    <h2 class="card-title">Nova Obra</h2>
                </div>

                <div class="card-body">
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

                    <form action="{{ route('works.store') }}" method="POST">
                        @csrf

                        @include('works.partials.form', [
                            'work' => null,
                        ])

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                Guardar Obra
                            </button>

                            <a href="{{ route('works.index') }}" class="btn btn-default">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection

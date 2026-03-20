@extends('layouts.admin')

@section('title', 'Novo Artigo')

@section('content')
    <div class="row">
        <div class="col">
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Novo Artigo / Serviço</h2>
                </header>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    @endif

                    <form action="{{ route('items.store') }}" method="POST">
                        @csrf

                        @include('items.partials.form')

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('items.index') }}" class="btn btn-light">
                                Voltar
                            </a>

                            <button type="submit" class="btn btn-primary">
                                Criar artigo
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Editar Dados da Empresa')

@section('content')
    <section role="main" class="content-body">
        <header class="page-header">
            <h2>Editar Dados da Empresa</h2>
        </header>

        <form
            action="{{ route('company-profile.update') }}"
            method="POST"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            @include('company-profile.partials.form')

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    Gravar
                </button>

                <a href="{{ route('company-profile.show') }}" class="btn btn-default">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
@endsection

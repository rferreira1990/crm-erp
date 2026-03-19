@extends('layouts.admin')

@section('title', 'Editar Cliente')

@section('content')
<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title mb-0">Editar Cliente</h2>
            </header>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Existem erros no formulário.</strong>
                    </div>
                @endif

                <form action="{{ route('customers.update', $customer) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @include('customers.partials.form', ['customer' => $customer])

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-light border">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

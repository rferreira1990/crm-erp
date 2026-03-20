@extends('layouts.admin')

@section('title', 'Editar Marca')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Editar Marca</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('brands.update', $brand) }}" method="POST">
            @csrf
            @method('PUT')

            @include('brands.partials.form', ['brand' => $brand])
        </form>
    </div>
</section>
@endsection

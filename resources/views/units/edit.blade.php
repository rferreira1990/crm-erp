@extends('layouts.admin')

@section('title', 'Editar Unidade')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Editar Unidade</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('units.update', $unit) }}" method="POST">
            @csrf
            @method('PUT')

            @include('units.partials.form', ['unit' => $unit])
        </form>
    </div>
</section>
@endsection

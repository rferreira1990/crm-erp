@extends('layouts.admin')

@section('title', 'Editar Familia de Artigos')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Editar Familia de Artigos</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('item-families.update', $item_family) }}" method="POST">
            @csrf
            @method('PUT')

            @include('item-families.partials.form')
        </form>
    </div>
</section>
@endsection

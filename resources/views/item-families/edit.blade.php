@extends('layouts.admin')

@section('title', 'Editar Família de Artigos')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Editar Família de Artigos</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('item-families.update', $item_family) }}" method="POST">
            @csrf
            @method('PUT')

            @include('item-families.partials.form', ['item_family' => $item_family])
        </form>
    </div>
</section>
@endsection

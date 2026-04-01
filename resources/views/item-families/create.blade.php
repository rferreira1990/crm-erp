@extends('layouts.admin')

@section('title', 'Nova Familia de Artigos')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Nova Familia de Artigos</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('item-families.store') }}" method="POST">
            @csrf

            @include('item-families.partials.form')
        </form>
    </div>
</section>
@endsection

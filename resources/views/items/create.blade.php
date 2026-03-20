@extends('layouts.admin')

@section('title', 'Novo Artigo')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Novo Artigo / Serviço</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('items.store') }}" method="POST">
            @csrf

            @include('items.partials.form')
        </form>
    </div>
</section>
@endsection

@extends('layouts.admin')

@section('title', 'Nova Marca')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Nova Marca</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('brands.store') }}" method="POST">
            @csrf

            @include('brands.partials.form')
        </form>
    </div>
</section>
@endsection

@extends('layouts.admin')

@section('title', 'Nova Unidade')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Nova Unidade</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('units.store') }}" method="POST">
            @csrf

            @include('units.partials.form')
        </form>
    </div>
</section>
@endsection

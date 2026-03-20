@extends('layouts.admin')

@section('title', 'Nova Taxa de IVA')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Nova Taxa de IVA</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('tax-rates.store') }}" method="POST">
            @csrf

            @include('tax-rates.partials.form')
        </form>
    </div>
</section>
@endsection

@extends('layouts.admin')

@section('title', 'Novo Motivo de Isenção')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Novo Motivo de Isenção</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('tax-exemption-reasons.store') }}" method="POST">
            @csrf

            @include('tax-exemption-reasons.partials.form')
        </form>
    </div>
</section>
@endsection

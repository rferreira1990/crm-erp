@extends('layouts.admin')

@section('title', 'Editar Taxa de IVA')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Editar Taxa de IVA</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('tax-rates.update', $tax_rate) }}" method="POST">
            @csrf
            @method('PUT')

            @include('tax-rates.partials.form', ['tax_rate' => $tax_rate])
        </form>
    </div>
</section>
@endsection

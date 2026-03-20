@extends('layouts.admin')

@section('title', 'Editar Motivo de Isenção')

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title mb-0">Editar Motivo de Isenção</h2>
    </header>

    <div class="card-body">
        <form action="{{ route('tax-exemption-reasons.update', $tax_exemption_reason) }}" method="POST">
            @csrf
            @method('PUT')

            @include('tax-exemption-reasons.partials.form', ['tax_exemption_reason' => $tax_exemption_reason])
        </form>
    </div>
</section>
@endsection

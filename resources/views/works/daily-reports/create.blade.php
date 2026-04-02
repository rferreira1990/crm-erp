@extends('layouts.admin')

@section('title', 'Novo Registo Diario')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Novo Registo Diario</h2>
        <div class="text-muted">Obra {{ $work->code }} - {{ $work->name }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-outline-secondary">Voltar ao diario</a>
        <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">Voltar a obra</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form action="{{ route('works.daily-reports.store', $work) }}" method="POST">
    @csrf

    @include('works.daily-reports.partials.form', [
        'work' => $work,
        'dailyReport' => $dailyReport,
        'availableItems' => $availableItems,
        'dayStatuses' => $dayStatuses,
    ])

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Guardar registo</button>
    </div>
</form>
@endsection


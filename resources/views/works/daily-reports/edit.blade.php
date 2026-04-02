@extends('layouts.admin')

@section('title', 'Editar Registo Diario')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Editar Registo Diario</h2>
        <div class="text-muted">
            Obra {{ $work->code }} - {{ $work->name }} | Data {{ $dailyReport->report_date?->format('d/m/Y') ?? '-' }}
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('works.daily-reports.show', [$work, $dailyReport]) }}" class="btn btn-outline-secondary">Voltar ao registo</a>
        <a href="{{ route('works.daily-reports.index', $work) }}" class="btn btn-outline-secondary">Voltar ao diario</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form action="{{ route('works.daily-reports.update', [$work, $dailyReport]) }}" method="POST">
    @csrf
    @method('PUT')

    @include('works.daily-reports.partials.form', [
        'work' => $work,
        'dailyReport' => $dailyReport,
        'dayStatuses' => $dayStatuses,
    ])

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('works.daily-reports.show', [$work, $dailyReport]) }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Atualizar registo</button>
    </div>
</form>
@endsection

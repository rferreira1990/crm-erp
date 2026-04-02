@extends('layouts.admin')

@section('title', 'Novo Template de Checklist')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Novo Template de Checklist</h2>

    <a href="{{ route('work-checklist-templates.index') }}" class="btn btn-outline-secondary">Voltar</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-2">Nao foi possivel guardar. Corrige os erros:</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('work-checklist-templates._form', [
    'template' => $template,
    'templateItems' => $templateItems,
    'action' => route('work-checklist-templates.store'),
    'method' => 'POST',
    'submitLabel' => 'Guardar template',
])
@endsection

@extends('layouts.admin')

@section('title', 'Templates de Checklist')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Templates de Checklist</h2>
        <div class="text-muted">Modelos reutilizaveis para carregar checklists em qualquer obra.</div>
    </div>

    <div class="d-flex gap-2">
        @can('works.update')
            <form method="POST" action="{{ route('work-checklist-templates.load-defaults') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Carregar templates base</button>
            </form>
            <a href="{{ route('work-checklist-templates.create') }}" class="btn btn-primary">Novo template</a>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <strong>Templates base disponiveis</strong>
    </div>
    <div class="card-body">
        @if ($defaultTemplates->count())
            <ul class="mb-0 ps-3">
                @foreach ($defaultTemplates as $defaultTemplate)
                    <li>
                        <strong>{{ $defaultTemplate['name'] }}</strong>
                        <span class="text-muted">({{ $defaultTemplate['items']->count() }} itens)</span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-muted">Sem templates base configurados.</div>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Templates criados</strong>
        <span class="badge bg-light text-dark border">{{ $templates->count() }}</span>
    </div>
    <div class="card-body">
        @if ($templates->count())
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descricao</th>
                            <th>Itens</th>
                            <th>Obrigatorios</th>
                            <th>Ativo</th>
                            <th>Ordem</th>
                            <th style="width: 180px;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->description ?: '-' }}</td>
                                <td>{{ (int) $template->items_count }}</td>
                                <td>{{ (int) $template->items->where('is_required', true)->count() }}</td>
                                <td>
                                    @if ($template->is_active)
                                        <span class="badge bg-success">Sim</span>
                                    @else
                                        <span class="badge bg-secondary">Nao</span>
                                    @endif
                                </td>
                                <td>{{ (int) $template->sort_order }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @can('works.update')
                                            <a href="{{ route('work-checklist-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                                                Editar
                                            </a>

                                            <form method="POST" action="{{ route('work-checklist-templates.destroy', $template) }}" onsubmit="return confirm('Apagar este template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Apagar</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">Ainda nao existem templates de checklist.</div>
        @endif
    </div>
</div>
@endsection

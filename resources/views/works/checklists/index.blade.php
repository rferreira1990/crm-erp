@extends('layouts.admin')

@section('title', 'Checklists da Obra')

@section('content')
@php
    $canUpdateWork = auth()->user()?->can('works.update');
    $canManageOperationalData = $canUpdateWork && $work->isEditable();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Checklists da Obra</h2>
        <div class="text-muted">{{ $work->code }} - {{ $work->name }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">Voltar a obra</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if (! $work->isEditable())
    <div class="alert alert-info">
        Esta obra encontra-se {{ strtolower($work->status_label) }}. Checklists em modo de consulta.
    </div>
@endif

@if ($canManageOperationalData && $checklistTemplates->count())
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <strong>Carregar checklist default</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('works.checklists.templates.apply', $work) }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Template</label>
                    <select name="template_key" class="form-select @error('template_key') is-invalid @enderror" required>
                        <option value="">Selecionar...</option>
                        @foreach ($checklistTemplates as $template)
                            <option value="{{ $template['key'] }}" @selected(old('template_key') === $template['key'])>
                                {{ $template['name'] }} ({{ $template['items_count'] }} itens)
                            </option>
                        @endforeach
                    </select>
                    @error('template_key')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Descricao</label>
                    <div class="form-control bg-light">
                        Escolhe um template default para carregar uma checklist completa de uma vez.
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-outline-primary">Carregar template</button>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Checklists</strong>
        <span class="badge bg-light text-dark border">{{ $checklists->count() }}</span>
    </div>
    <div class="card-body">
        @if ($canManageOperationalData)
            <form method="POST" action="{{ route('works.checklists.store', $work) }}" class="border rounded p-3 mb-4 bg-light">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nome da checklist <span class="text-danger">*</span></label>
                        <input type="text" name="checklist_name" class="form-control @error('checklist_name') is-invalid @enderror" value="{{ old('checklist_name') }}" maxlength="255" required>
                        @error('checklist_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Descricao</label>
                        <input type="text" name="checklist_description" class="form-control @error('checklist_description') is-invalid @enderror" value="{{ old('checklist_description') }}" maxlength="5000">
                        @error('checklist_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Adicionar checklist</button>
                    </div>
                </div>
            </form>
        @endif

        @if ($checklists->count())
            @foreach ($checklists as $checklist)
                @php
                    $totalItems = $checklist->totalItemsCount();
                    $completedItems = $checklist->completedItemsCount();
                    $pendingRequiredItems = $checklist->pendingRequiredItemsCount();
                @endphp
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <div class="fw-semibold">{{ $checklist->name }}</div>
                            @if ($checklist->description)
                                <div class="small text-muted">{{ $checklist->description }}</div>
                            @endif
                            <div class="small text-muted mt-1">
                                Progresso:
                                <span data-checklist-progress="{{ $checklist->id }}">{{ $completedItems }}/{{ $totalItems }}</span>
                                @if ($pendingRequiredItems > 0)
                                    <span class="badge bg-danger ms-2" data-checklist-required="{{ $checklist->id }}">
                                        {{ $pendingRequiredItems }} obrigatorio(s) pendente(s)
                                    </span>
                                @else
                                    <span class="badge bg-success ms-2" data-checklist-required="{{ $checklist->id }}">Obrigatorios ok</span>
                                @endif
                            </div>
                        </div>
                        @if ($canManageOperationalData)
                            <form method="POST" action="{{ route('works.checklists.destroy', [$work, $checklist]) }}" onsubmit="return confirm('Remover esta checklist e todos os itens?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remover checklist</button>
                            </form>
                        @endif
                    </div>

                    @if ($canManageOperationalData)
                        <form method="POST" action="{{ route('works.checklists.items.store', [$work, $checklist]) }}" class="row g-2 mb-3 bg-light border rounded p-2">
                            @csrf
                            <div class="col-md-8">
                                <label class="form-label">Novo item <span class="text-danger">*</span></label>
                                <input type="text" name="item_description" class="form-control @error('item_description') is-invalid @enderror" maxlength="500" required>
                                @error('item_description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="item_is_required" id="is_required_{{ $checklist->id }}" value="1">
                                    <label class="form-check-label" for="is_required_{{ $checklist->id }}">
                                        Obrigatorio
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end justify-content-end">
                                <button type="submit" class="btn btn-sm btn-primary mb-1">Adicionar item</button>
                            </div>
                        </form>
                    @endif

                    @if ($checklist->items->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Estado</th>
                                        <th>Item</th>
                                        <th style="width: 280px;">Concluido por</th>
                                        @if ($canManageOperationalData)
                                            <th style="width: 110px;">Acoes</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($checklist->items as $checklistItem)
                                        <tr data-checklist-item-row="{{ $checklistItem->id }}">
                                            <td class="text-center">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input js-checklist-toggle"
                                                    data-url="{{ route('works.checklists.items.toggle', [$work, $checklist, $checklistItem]) }}"
                                                    data-checklist-id="{{ $checklist->id }}"
                                                    data-item-id="{{ $checklistItem->id }}"
                                                    @checked($checklistItem->is_completed)
                                                    @disabled(! $canManageOperationalData)
                                                >
                                            </td>
                                            <td>
                                                <span data-checklist-item-description="{{ $checklistItem->id }}" class="{{ $checklistItem->is_completed ? 'text-decoration-line-through text-muted' : '' }}">
                                                    {{ $checklistItem->description }}
                                                </span>
                                                @if ($checklistItem->is_required)
                                                    <span class="badge bg-warning text-dark ms-2">Obrigatorio</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span data-checklist-item-meta="{{ $checklistItem->id }}">
                                                    @if ($checklistItem->is_completed)
                                                        {{ $checklistItem->completedBy?->name ?? '-' }}
                                                        @if ($checklistItem->completed_at)
                                                            &middot; {{ $checklistItem->completed_at->format('d/m/Y H:i') }}
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </span>
                                            </td>
                                            @if ($canManageOperationalData)
                                                <td>
                                                    <form method="POST" action="{{ route('works.checklists.items.destroy', [$work, $checklist, $checklistItem]) }}" onsubmit="return confirm('Remover este item?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                                    </form>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted">Sem itens nesta checklist.</div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="text-muted">Sem checklists para esta obra.</div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : @json(csrf_token());

        document.querySelectorAll('.js-checklist-toggle').forEach(function (toggleInput) {
            toggleInput.addEventListener('change', function () {
                const url = toggleInput.dataset.url;
                const checklistId = toggleInput.dataset.checklistId;
                const itemId = toggleInput.dataset.itemId;
                const nextValue = toggleInput.checked;

                if (!url || !checklistId || !itemId) {
                    return;
                }

                toggleInput.disabled = true;

                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        is_completed: nextValue ? 1 : 0,
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Erro ao atualizar item.');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        if (!payload || payload.ok !== true) {
                            throw new Error('Resposta invalida do servidor.');
                        }

                        const descriptionElement = document.querySelector('[data-checklist-item-description="' + itemId + '"]');
                        const metaElement = document.querySelector('[data-checklist-item-meta="' + itemId + '"]');
                        const progressElement = document.querySelector('[data-checklist-progress="' + checklistId + '"]');
                        const requiredElement = document.querySelector('[data-checklist-required="' + checklistId + '"]');

                        if (descriptionElement) {
                            descriptionElement.classList.toggle('text-decoration-line-through', payload.item.is_completed);
                            descriptionElement.classList.toggle('text-muted', payload.item.is_completed);
                        }

                        if (metaElement) {
                            if (payload.item.is_completed) {
                                const byName = payload.item.completed_by_name || '-';
                                const at = payload.item.completed_at ? (' · ' + payload.item.completed_at) : '';
                                metaElement.textContent = byName + at;
                            } else {
                                metaElement.textContent = '-';
                            }
                        }

                        if (progressElement) {
                            progressElement.textContent = payload.checklist.completed_items + '/' + payload.checklist.total_items;
                        }

                        if (requiredElement) {
                            const pendingRequired = Number(payload.checklist.pending_required_items || 0);
                            if (pendingRequired > 0) {
                                requiredElement.classList.remove('bg-success');
                                requiredElement.classList.add('bg-danger');
                                requiredElement.textContent = pendingRequired + ' obrigatorio(s) pendente(s)';
                            } else {
                                requiredElement.classList.remove('bg-danger');
                                requiredElement.classList.add('bg-success');
                                requiredElement.textContent = 'Obrigatorios ok';
                            }
                        }
                    })
                    .catch(function () {
                        toggleInput.checked = !nextValue;
                        window.alert('Nao foi possivel atualizar o estado do item da checklist.');
                    })
                    .finally(function () {
                        toggleInput.disabled = false;
                    });
            });
        });
    });
</script>
@endsection

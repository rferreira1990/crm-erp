@php
    $initialItems = old('items');

    if (!is_array($initialItems) || count($initialItems) === 0) {
        $initialItems = collect($templateItems ?? [])
            ->map(function ($item): array {
                if (is_array($item)) {
                    return [
                        'description' => (string) ($item['description'] ?? ''),
                        'is_required' => (bool) ($item['is_required'] ?? false),
                        'sort_order' => (int) ($item['sort_order'] ?? 0),
                    ];
                }

                return [
                    'description' => (string) ($item->description ?? ''),
                    'is_required' => (bool) ($item->is_required ?? false),
                    'sort_order' => (int) ($item->sort_order ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    if (count($initialItems) === 0) {
        $initialItems = [
            ['description' => '', 'is_required' => false, 'sort_order' => 0],
        ];
    }
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row">
        <div class="col-xl-10">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <strong>Dados do template</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $template->name) }}"
                                maxlength="255"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="sort_order" class="form-label">Ordem</label>
                            <input
                                type="number"
                                id="sort_order"
                                name="sort_order"
                                class="form-control @error('sort_order') is-invalid @enderror"
                                value="{{ old('sort_order', $template->sort_order ?? 0) }}"
                                min="0"
                                max="9999"
                            >
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="is_active"
                                    id="is_active"
                                    value="1"
                                    {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="is_active">Ativo</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Descricao</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="form-control @error('description') is-invalid @enderror"
                                maxlength="5000"
                            >{{ old('description', $template->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Itens do template</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-template-item">Adicionar item</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="template-items-table">
                            <thead>
                                <tr>
                                    <th>Descricao <span class="text-danger">*</span></th>
                                    <th style="width: 140px;">Obrigatorio</th>
                                    <th style="width: 120px;">Ordem</th>
                                    <th style="width: 90px;">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($initialItems as $index => $item)
                                    <tr>
                                        <td>
                                            <input
                                                type="text"
                                                name="items[{{ $index }}][description]"
                                                class="form-control @error('items.' . $index . '.description') is-invalid @enderror"
                                                value="{{ $item['description'] ?? '' }}"
                                                maxlength="500"
                                                required
                                            >
                                            @error('items.' . $index . '.description')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" name="items[{{ $index }}][is_required]" value="0">
                                            <input
                                                type="checkbox"
                                                name="items[{{ $index }}][is_required]"
                                                value="1"
                                                class="form-check-input"
                                                {{ !empty($item['is_required']) ? 'checked' : '' }}
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][sort_order]"
                                                class="form-control @error('items.' . $index . '.sort_order') is-invalid @enderror"
                                                value="{{ isset($item['sort_order']) ? (int) $item['sort_order'] : (($index + 1) * 10) }}"
                                                min="0"
                                                max="9999"
                                            >
                                            @error('items.' . $index . '.sort_order')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-template-item">Remover</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-muted mt-2">
                        Pelo menos 1 item e obrigatorio no template.
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script src="{{ asset('porto/js/pages/work-checklist-template-form.js') }}"></script>
@endpush

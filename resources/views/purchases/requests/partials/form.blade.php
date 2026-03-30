@php
    $currentItems = collect(old('items', isset($purchaseRequest) && $purchaseRequest->relationLoaded('items')
        ? $purchaseRequest->items->map(fn ($line) => [
            'item_id' => $line->item_id,
            'description' => $line->description,
            'qty' => $line->qty,
            'unit_snapshot' => $line->unit_snapshot,
            'notes' => $line->notes,
        ])->toArray()
        : []));

    if ($currentItems->isEmpty()) {
        $currentItems = collect([[
            'item_id' => null,
            'description' => '',
            'qty' => '',
            'unit_snapshot' => '',
            'notes' => '',
        ]]);
    }

    $catalogOptions = $itemsCatalog->map(function ($item) {
        return [
            'id' => $item->id,
            'label' => $item->code . ' - ' . $item->name,
            'name' => $item->name,
            'unit' => $item->unit?->code,
        ];
    })->values();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="work_id" class="form-label">Obra associada (opcional)</label>
        <select name="work_id" id="work_id" class="form-select @error('work_id') is-invalid @enderror">
            <option value="">-</option>
            @foreach ($works as $work)
                <option value="{{ $work->id }}" @selected((int) old('work_id', $purchaseRequest->work_id ?? 0) === (int) $work->id)>
                    {{ $work->code }} - {{ $work->name }}
                </option>
            @endforeach
        </select>
        @error('work_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="deadline_at" class="form-label">Prazo para propostas</label>
        <input
            type="date"
            name="deadline_at"
            id="deadline_at"
            class="form-control @error('deadline_at') is-invalid @enderror"
            value="{{ old('deadline_at', optional($purchaseRequest->deadline_at)->toDateString()) }}"
        >
        @error('deadline_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notas</label>
        <textarea
            name="notes"
            id="notes"
            rows="3"
            class="form-control @error('notes') is-invalid @enderror"
        >{{ old('notes', $purchaseRequest->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Linhas do RFQ</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-rfq-line">Adicionar linha</button>
        </div>

        @error('items')
            <div class="alert alert-danger py-2">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="rfq-items-table">
                <thead>
                    <tr>
                        <th style="width: 22%">Artigo</th>
                        <th style="width: 26%">Descricao</th>
                        <th style="width: 12%">Qtd</th>
                        <th style="width: 12%">Unidade</th>
                        <th>Notas</th>
                        <th style="width: 7%" class="text-center">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($currentItems as $index => $line)
                        <tr>
                            <td>
                                <select name="items[{{ $index }}][item_id]" class="form-select rfq-item-select @error('items.' . $index . '.item_id') is-invalid @enderror">
                                    <option value="">-</option>
                                    @foreach ($itemsCatalog as $catalogItem)
                                        <option
                                            value="{{ $catalogItem->id }}"
                                            data-name="{{ $catalogItem->name }}"
                                            data-unit="{{ $catalogItem->unit?->code }}"
                                            @selected((int) ($line['item_id'] ?? 0) === (int) $catalogItem->id)
                                        >
                                            {{ $catalogItem->code }} - {{ $catalogItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('items.' . $index . '.item_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][description]"
                                    class="form-control rfq-desc @error('items.' . $index . '.description') is-invalid @enderror"
                                    value="{{ $line['description'] ?? '' }}"
                                    maxlength="255"
                                    required
                                >
                                @error('items.' . $index . '.description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="items[{{ $index }}][qty]"
                                    class="form-control @error('items.' . $index . '.qty') is-invalid @enderror"
                                    value="{{ $line['qty'] ?? '' }}"
                                    min="0.001"
                                    step="0.001"
                                    required
                                >
                                @error('items.' . $index . '.qty')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][unit_snapshot]"
                                    class="form-control rfq-unit @error('items.' . $index . '.unit_snapshot') is-invalid @enderror"
                                    value="{{ $line['unit_snapshot'] ?? '' }}"
                                    maxlength="100"
                                >
                                @error('items.' . $index . '.unit_snapshot')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="items[{{ $index }}][notes]"
                                    class="form-control @error('items.' . $index . '.notes') is-invalid @enderror"
                                    value="{{ $line['notes'] ?? '' }}"
                                    maxlength="2000"
                                >
                                @error('items.' . $index . '.notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger rfq-remove-line">X</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.querySelector('#rfq-items-table tbody');
        const addButton = document.getElementById('add-rfq-line');
        let rowIndex = tableBody ? tableBody.querySelectorAll('tr').length : 0;

        const catalogOptions = @json($catalogOptions);

        function renderOptions() {
            let html = '<option value="">-</option>';
            catalogOptions.forEach(function (option) {
                const safeName = (option.name || '').replace(/"/g, '&quot;');
                const safeUnit = (option.unit || '').replace(/"/g, '&quot;');
                html += '<option value="' + option.id + '" data-name="' + safeName + '" data-unit="' + safeUnit + '">' + option.label + '</option>';
            });
            return html;
        }

        function bindRow(row) {
            const removeButton = row.querySelector('.rfq-remove-line');
            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    if (tableBody.querySelectorAll('tr').length <= 1) {
                        return;
                    }
                    row.remove();
                });
            }

            const itemSelect = row.querySelector('.rfq-item-select');
            if (itemSelect) {
                itemSelect.addEventListener('change', function () {
                    const selected = itemSelect.options[itemSelect.selectedIndex];
                    const descField = row.querySelector('.rfq-desc');
                    const unitField = row.querySelector('.rfq-unit');

                    if (!selected || !selected.value) {
                        return;
                    }

                    if (descField && !descField.value.trim()) {
                        descField.value = selected.getAttribute('data-name') || '';
                    }
                    if (unitField && !unitField.value.trim()) {
                        unitField.value = selected.getAttribute('data-unit') || '';
                    }
                });
            }
        }

        if (tableBody) {
            tableBody.querySelectorAll('tr').forEach(bindRow);
        }

        if (addButton && tableBody) {
            addButton.addEventListener('click', function () {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <select name="items[${rowIndex}][item_id]" class="form-select rfq-item-select">
                            ${renderOptions()}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][description]" class="form-control rfq-desc" maxlength="255" required>
                    </td>
                    <td>
                        <input type="number" name="items[${rowIndex}][qty]" class="form-control" min="0.001" step="0.001" required>
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][unit_snapshot]" class="form-control rfq-unit" maxlength="100">
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][notes]" class="form-control" maxlength="2000">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger rfq-remove-line">X</button>
                    </td>
                `;
                tableBody.appendChild(tr);
                bindRow(tr);
                rowIndex++;
            });
        }
    });
</script>
@endpush

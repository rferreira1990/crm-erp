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

    $initialItemOptions = collect($rfqItemInitialOptions ?? [])->keyBy('id');
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('porto/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}">
    <style>
        .rfq-item-select-wrap .select2-container {
            width: 100% !important;
        }

        .rfq-item-select-wrap .select2-selection--single {
            min-height: 38px;
        }

        .rfq-item-option {
            line-height: 1.3;
        }

        .rfq-item-option small {
            color: #6c757d;
            display: block;
        }
    </style>
@endpush

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
            <div class="d-flex gap-2">
                @can('items.create')
                    <a href="{{ route('items.create') }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                        Criar artigo
                    </a>
                @endcan
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-rfq-line">Adicionar linha</button>
            </div>
        </div>

        @error('items')
            <div class="alert alert-danger py-2">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table
                class="table table-bordered align-middle"
                id="rfq-items-table"
                data-item-search-url="{{ route('api.items.search') }}"
            >
                <thead>
                    <tr>
                        <th style="width: 24%">Artigo</th>
                        <th style="width: 24%">Descricao</th>
                        <th style="width: 12%">Qtd</th>
                        <th style="width: 12%">Unidade</th>
                        <th>Notas</th>
                        <th style="width: 7%" class="text-center">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($currentItems as $index => $line)
                        @php
                            $selectedItemId = (int) ($line['item_id'] ?? 0);
                            $selectedItem = $selectedItemId > 0 ? $initialItemOptions->get($selectedItemId) : null;
                        @endphp
                        <tr>
                            <td>
                                <div class="rfq-item-select-wrap">
                                    <select
                                        name="items[{{ $index }}][item_id]"
                                        class="form-select rfq-item-select @error('items.' . $index . '.item_id') is-invalid @enderror"
                                        data-placeholder="Pesquisar artigo por codigo ou nome..."
                                    >
                                        <option value=""></option>
                                        @if ($selectedItemId > 0)
                                            <option
                                                value="{{ $selectedItemId }}"
                                                data-name="{{ $selectedItem['name'] ?? '' }}"
                                                data-description="{{ $selectedItem['description'] ?? ($selectedItem['name'] ?? '') }}"
                                                data-unit="{{ $selectedItem['unit'] ?? '' }}"
                                                selected
                                            >
                                                {{ $selectedItem['label'] ?? ('Artigo #' . $selectedItemId) }}
                                            </option>
                                        @endif
                                    </select>
                                </div>
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
    <script src="{{ asset('porto/vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('porto/vendor/select2/js/i18n/pt.js') }}"></script>
    <script src="{{ asset('porto/js/pages/purchase-request-form.js') }}"></script>
@endpush

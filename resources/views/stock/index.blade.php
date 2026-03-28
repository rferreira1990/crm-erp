@extends('layouts.admin')

@section('title', 'Movimentos de Stock')

@section('content')
@php
    $directionLabels = [
        'in' => 'Entrada',
        'out' => 'Saida',
        'adjustment' => 'Ajuste',
    ];

    $directionClasses = [
        'in' => 'bg-success',
        'out' => 'bg-danger',
        'adjustment' => 'bg-warning text-dark',
    ];

    $movementTypeLabels = [
        'work_material' => 'Material de obra',
        'manual_entry' => 'Entrada manual',
        'manual_exit' => 'Saida manual',
        'manual_adjustment' => 'Ajuste manual',
    ];
@endphp

<section class="card mb-4">
    <header class="card-header">
        <h2 class="card-title mb-0">Filtros de movimentos</h2>
    </header>

    <div class="card-body">
        <form method="GET" action="{{ route('stock.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Pesquisar artigo</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Codigo ou nome do artigo"
                    >
                </div>

                <div class="col-md-2">
                    <label for="movement_type" class="form-label">Tipo</label>
                    <select id="movement_type" name="movement_type" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($movementTypes as $movementType)
                            <option value="{{ $movementType }}" @selected(($filters['movement_type'] ?? '') === $movementType)>
                                {{ $movementTypeLabels[$movementType] ?? ucfirst(str_replace('_', ' ', $movementType)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="direction" class="form-label">Direcao</label>
                    <select id="direction" name="direction" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($directionLabels as $directionKey => $directionLabel)
                            <option value="{{ $directionKey }}" @selected(($filters['direction'] ?? '') === $directionKey)>{{ $directionLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="user_id" class="form-label">Utilizador</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === (int) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <label for="date_from" class="form-label">Data de</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-md-1">
                    <label for="date_to" class="form-label">Data ate</label>
                    <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-md-1">
                    <label for="per_page" class="form-label">Por pag.</label>
                    <select id="per_page" name="per_page" class="form-select">
                        @foreach ([10, 25, 50, 100] as $perPage)
                            <option value="{{ $perPage }}" @selected((int) ($filters['per_page'] ?? 25) === $perPage)>
                                {{ $perPage }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="only_works" name="only_works" value="1" @checked((bool) ($filters['only_works'] ?? false))>
                        <label class="form-check-label" for="only_works">
                            Apenas movimentos ligados a obras
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('stock.index') }}" class="btn btn-light btn-sm border">Limpar</a>
            </div>
        </form>
    </div>
</section>

@can('stock.create')
    <section class="card mb-4">
        <header class="card-header">
            <h2 class="card-title mb-0">Novo movimento manual</h2>
        </header>

        <div class="card-body">
            <form method="POST" action="{{ route('stock.movements.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="manual_item_id" class="form-label">Artigo</label>
                        <select id="manual_item_id" name="item_id" class="form-select @error('item_id') is-invalid @enderror" required>
                            <option value="">Selecionar...</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" data-stock="{{ number_format((float) $item->current_stock, 3, '.', '') }}" @selected((int) old('item_id') === (int) $item->id)>
                                    {{ $item->code }} - {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('item_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label for="manual_movement_type" class="form-label">Tipo</label>
                        <select id="manual_movement_type" name="movement_type" class="form-select @error('movement_type') is-invalid @enderror" required>
                            <option value="manual_entry" @selected(old('movement_type') === 'manual_entry')>Entrada manual</option>
                            <option value="manual_exit" @selected(old('movement_type') === 'manual_exit')>Saida manual</option>
                            @if ($canManualAdjustment)
                                <option value="manual_adjustment" @selected(old('movement_type') === 'manual_adjustment')>Ajuste manual</option>
                            @endif
                        </select>
                        @error('movement_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label for="manual_direction" class="form-label">Direcao</label>
                        <select id="manual_direction" name="direction" class="form-select @error('direction') is-invalid @enderror" required>
                            <option value="in" @selected(old('direction') === 'in')>Entrada</option>
                            <option value="out" @selected(old('direction') === 'out')>Saida</option>
                            <option value="adjustment" @selected(old('direction') === 'adjustment')>Ajuste</option>
                        </select>
                        @error('direction')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-1">
                        <label for="manual_quantity" class="form-label">Qtd</label>
                        <input type="number" id="manual_quantity" name="quantity" step="0.001" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label for="manual_occurred_at" class="form-label">Data/hora</label>
                        <input type="datetime-local" id="manual_occurred_at" name="occurred_at" class="form-control @error('occurred_at') is-invalid @enderror" value="{{ old('occurred_at') }}">
                        @error('occurred_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="manual_reason" class="form-label">Motivo <span class="text-danger">*</span></label>
                        <select id="manual_reason" name="manual_reason" class="form-select @error('manual_reason') is-invalid @enderror" required>
                            <option value="">Selecionar...</option>
                            @foreach ($manualReasons as $reasonKey => $reasonLabel)
                                <option value="{{ $reasonKey }}" @selected(old('manual_reason') === $reasonKey)>{{ $reasonLabel }}</option>
                            @endforeach
                        </select>
                        @error('manual_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label for="manual_notes" class="form-label">Justificacao <span class="text-danger">*</span></label>
                        <input type="text" id="manual_notes" name="notes" class="form-control @error('notes') is-invalid @enderror" value="{{ old('notes') }}" maxlength="5000" required>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <div id="manual-stock-hint" class="small text-muted"></div>
                    </div>

                    <div class="col-md-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm">Registar movimento</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endcan

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Movimentos de stock</h2>
        <span class="badge bg-light text-dark border">{{ $movements->total() }}</span>
    </header>

    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($movements->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data/hora</th>
                            <th>Artigo</th>
                            <th>Tipo</th>
                            <th>Direcao</th>
                            <th>Quantidade</th>
                            <th>Stock antes</th>
                            <th>Stock depois</th>
                            <th>Origem</th>
                            <th>Utilizador</th>
                            <th>Motivo</th>
                            <th>Ligacoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            @php
                                $directionLabel = $directionLabels[$movement->direction] ?? ucfirst((string) $movement->direction);
                                $directionClass = $directionClasses[$movement->direction] ?? 'bg-secondary';
                                $movementTypeLabel = $movementTypeLabels[$movement->movement_type] ?? ucfirst(str_replace('_', ' ', (string) $movement->movement_type));
                                $workMaterial = $movement->workMaterial;
                                $work = $workMaterial?->work;
                                $isWorkSource = ($movement->source_type === \App\Models\StockMovement::TYPE_WORK_MATERIAL) || (bool) $movement->work_material_id;
                            @endphp
                            <tr>
                                <td>{{ $movement->occurred_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                                <td>
                                    @if ($movement->item)
                                        <div class="fw-semibold">{{ $movement->item->code }}</div>
                                        <div class="small text-muted">{{ $movement->item->name }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $movementTypeLabel }}</td>
                                <td><span class="badge {{ $directionClass }}">{{ $directionLabel }}</span></td>
                                <td>{{ number_format((float) $movement->quantity, 3, ',', '.') }}</td>
                                <td>{{ number_format((float) $movement->stock_before, 3, ',', '.') }}</td>
                                <td>{{ number_format((float) $movement->stock_after, 3, ',', '.') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $movement->source_type ?: '-' }}</div>
                                    @if ($movement->source_id)
                                        <div class="small text-muted">ID origem: {{ $movement->source_id }}</div>
                                    @endif
                                </td>
                                <td>{{ $movement->creator?->name ?? '-' }}</td>
                                <td>
                                    @if ($movement->manual_reason)
                                        <div>{{ $manualReasons[$movement->manual_reason] ?? ucfirst(str_replace('_', ' ', $movement->manual_reason)) }}</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($isWorkSource && $work)
                                        <div>
                                            <a href="{{ route('works.show', $work) }}">Obra {{ $work->code }}</a>
                                            <span class="small text-muted">- {{ $work->name }}</span>
                                        </div>
                                        @if ($movement->work_material_id)
                                            <div>
                                                <a href="{{ route('works.show', $work) }}#work-material-{{ $movement->work_material_id }}">Material #{{ $movement->work_material_id }}</a>
                                            </div>
                                        @endif
                                        @if ($workMaterial?->description_snapshot)
                                            <div class="small text-muted">{{ $workMaterial->description_snapshot }}</div>
                                        @endif
                                    @elseif ($isWorkSource)
                                        <div class="small text-muted">Movimento ligado a obra/material.</div>
                                        @if ($movement->work_material_id)
                                            <div class="small text-muted">Material #{{ $movement->work_material_id }}</div>
                                        @endif
                                    @else
                                        <div class="small text-muted">{{ $movement->notes ?: 'Sem contexto adicional.' }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $movements->links() }}
            </div>
        @else
            <div class="text-muted">Sem movimentos de stock para os filtros aplicados.</div>
        @endif
    </div>
</section>

@can('stock.create')
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('manual_movement_type');
            const directionSelect = document.getElementById('manual_direction');
            const itemSelect = document.getElementById('manual_item_id');
            const hint = document.getElementById('manual-stock-hint');
            const canAdjust = {{ $canManualAdjustment ? 'true' : 'false' }};

            const syncDirection = function () {
                if (!typeSelect || !directionSelect) {
                    return;
                }

                if (typeSelect.value === 'manual_entry') {
                    directionSelect.value = 'in';
                } else if (typeSelect.value === 'manual_exit') {
                    directionSelect.value = 'out';
                } else {
                    directionSelect.value = canAdjust ? 'adjustment' : 'in';
                }
            };

            const syncStockHint = function () {
                if (!itemSelect || !hint) {
                    return;
                }

                const selected = itemSelect.options[itemSelect.selectedIndex];
                if (!selected || !selected.value) {
                    hint.textContent = '';
                    return;
                }

                const stockValue = Number(selected.getAttribute('data-stock') || 0).toFixed(3).replace('.', ',');
                hint.textContent = 'Stock atual do artigo: ' + stockValue;
            };

            if (typeSelect) {
                typeSelect.addEventListener('change', syncDirection);
                syncDirection();
            }

            if (itemSelect) {
                itemSelect.addEventListener('change', syncStockHint);
                syncStockHint();
            }
        });
    </script>
    @endpush
@endcan
@endsection


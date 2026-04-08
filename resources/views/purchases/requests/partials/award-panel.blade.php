@php
    $activeAward = $awardPreview['activeAward'] ?? null;
    $eligibleQuotes = collect($awardPreview['eligibleQuotes'] ?? []);
    $globalWinner = $awardPreview['global']['winnerQuote'] ?? null;
    $perLineBySupplier = collect($awardPreview['perLine']['bySupplier'] ?? []);
    $perLineItemsMap = collect($awardPreview['perLine']['itemsMap'] ?? []);
    $awardEmailSuppliers = $activeAward
        ? $activeAward->preparedOrders
            ->map(function ($order) {
                $supplier = $order->supplier;

                return [
                    'id' => (int) ($supplier?->id ?? 0),
                    'code' => $supplier?->code,
                    'name' => $supplier?->name,
                    'recipient_name' => $supplier?->contact_person ?: $supplier?->name,
                    'recipient_email' => $supplier?->habitual_order_email ?: $supplier?->email,
                ];
            })
            ->filter(fn ($row) => (int) ($row['id'] ?? 0) > 0)
            ->unique('id')
            ->values()
        : collect();
    $forcedQuoteOptions = $eligibleQuotes->map(function ($quote) {
        $comparisonTotal = (float) ($quote->comparison_total_amount ?? $quote->total_amount);

        return [
            'supplier_id' => (int) $quote->supplier_id,
            'supplier_name' => $quote->supplier_name_snapshot,
            'supplier_code' => $quote->supplier?->code,
            'quote_id' => (int) $quote->id,
            'total_amount' => $comparisonTotal,
            'lines_count' => (int) $quote->items->whereNotNull('unit_price')->count(),
            'currency' => $quote->currency,
        ];
    })->unique('supplier_id')->values();
    $perLineWinnerByRequestItemId = $perLineItemsMap
        ->filter(fn (array $row) => ! ($row['is_missing'] ?? true) && ! empty($row['winner']['supplier_id']))
        ->mapWithKeys(function (array $row) {
            $requestItem = $row['request_item'];
            $winner = $row['winner'];

            return [
                (int) $requestItem->id => [
                    'supplier_id' => (int) ($winner['supplier_id'] ?? 0),
                    'awarded_qty' => (float) ($winner['awarded_qty'] ?? 0),
                ],
            ];
        });
    $manualPartialRows = $purchaseRequest->items
        ->sortBy(fn ($item) => [(int) $item->sort_order, (int) $item->id])
        ->values()
        ->map(function ($requestItem, $rowIndex) use ($eligibleQuotes, $perLineWinnerByRequestItemId) {
            $supplierOptions = $eligibleQuotes
                ->map(function ($quote) use ($requestItem) {
                    $quoteItem = $quote->items->firstWhere('purchase_request_item_id', (int) $requestItem->id);
                    if (! $quoteItem || $quoteItem->unit_price === null) {
                        return null;
                    }

                    $requestedQty = (float) $requestItem->qty;
                    $quotedQty = $quoteItem->quoted_qty !== null
                        ? (float) $quoteItem->quoted_qty
                        : $requestedQty;
                    $maxQty = min($requestedQty, max(0.0, $quotedQty));
                    $unitPrice = (float) $quoteItem->unit_price;
                    $discountPercent = $quoteItem->discount_percent !== null
                        ? (float) $quoteItem->discount_percent
                        : 0.0;
                    $lineTotal = round($maxQty * $unitPrice * (1 - ($discountPercent / 100)), 2);

                    return [
                        'supplier_id' => (int) $quote->supplier_id,
                        'supplier_name' => $quote->supplier_name_snapshot,
                        'supplier_code' => $quote->supplier?->code,
                        'quote_id' => (int) $quote->id,
                        'quote_item_id' => (int) $quoteItem->id,
                        'quoted_qty' => $quotedQty,
                        'max_qty' => $maxQty,
                        'unit_price' => $unitPrice,
                        'discount_percent' => $discountPercent,
                        'supplier_item_reference' => $quoteItem->supplier_item_reference,
                        'line_total' => $lineTotal,
                    ];
                })
                ->filter()
                ->values();

            $rowOld = collect(old('manual_lines', []))->firstWhere(
                'purchase_request_item_id',
                (int) $requestItem->id
            );

            $winnerDefault = $perLineWinnerByRequestItemId->get((int) $requestItem->id);
            $defaultSupplierId = (int) ($winnerDefault['supplier_id'] ?? 0);
            $defaultQty = $winnerDefault && (float) ($winnerDefault['awarded_qty'] ?? 0) > 0
                ? (float) $winnerDefault['awarded_qty']
                : (float) $requestItem->qty;

            $selectedSupplierId = (int) ($rowOld['supplier_id'] ?? $defaultSupplierId);
            $awardedQty = $rowOld['awarded_qty'] ?? ($selectedSupplierId > 0 ? $defaultQty : 0);

            return [
                'index' => (int) $rowIndex,
                'request_item' => $requestItem,
                'supplier_options' => $supplierOptions,
                'selected_supplier_id' => $selectedSupplierId > 0 ? $selectedSupplierId : null,
                'awarded_qty' => $awardedQty,
            ];
        });
@endphp

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Adjudicacao e encomendas preparadas</h3>
        @can('purchases.award')
            @if ($purchaseRequest->isEditable() && $eligibleQuotes->isNotEmpty())
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#awardLowestTotalModal">Adjudicar ao mais barato (global)</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#awardLowestPerLineModal">Adjudicar por artigo mais barato</button>
                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#awardManualPartialModal">Encomenda parcial manual</button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#awardForcedSupplierModal">Forcar fornecedor</button>
                </div>
            @endif
        @endcan
    </header>
    <div class="card-body">
        @if ($activeAward)
            <div class="alert alert-success">
                <div><strong>Estado:</strong> Adjudicado</div>
                <div><strong>Modo:</strong> {{ $activeAward->modeLabel() }}</div>
                <div><strong>Decidido em:</strong> {{ $activeAward->decided_at?->format('d/m/Y H:i') ?: '-' }}</div>
                <div><strong>Utilizador:</strong> {{ $activeAward->decidedBy?->name ?: '-' }}</div>
                @if ($activeAward->mode === \App\Models\PurchaseRequestAward::MODE_LOWEST_TOTAL && $activeAward->selectedQuote)
                    <div><strong>Fornecedor vencedor global:</strong> {{ $activeAward->selectedQuote->supplier_name_snapshot }}</div>
                @endif
                @if ($activeAward->mode === \App\Models\PurchaseRequestAward::MODE_FORCED_SUPPLIER)
                    <div><strong>Fornecedor forcado:</strong> {{ $activeAward->forcedSupplier?->code ? $activeAward->forcedSupplier->code . ' - ' . $activeAward->forcedSupplier->name : ($activeAward->forcedSupplier?->name ?: '-') }}</div>
                    <div><strong>Justificacao:</strong> {{ $activeAward->justification ?: '-' }}</div>
                @endif
                <div><strong>Encomendas preparadas:</strong> {{ $activeAward->generated_orders_count }}</div>
                <div class="d-flex gap-2 flex-wrap mt-3">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('purchase-requests.awards.pdf', [$purchaseRequest, $activeAward]) }}" target="_blank">
                        Download PDF adjudicacao
                    </a>
                    @can('purchases.update')
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#sendAwardEmailModal">
                            Enviar adjudicacao por email
                        </button>
                    @endcan
                </div>
            </div>

            @if ($activeAward->preparedOrders->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fornecedor</th>
                                <th class="text-center">Linhas</th>
                                <th class="text-end">Qtd encomendada</th>
                                <th class="text-end">Qtd recebida</th>
                                <th class="text-end">Qtd devolvida</th>
                                <th class="text-end">Qtd liquida</th>
                                <th class="text-end">Qtd pendente</th>
                                <th class="text-end">Subtotal s/ IVA</th>
                                <th>Moeda</th>
                                <th>Cond. pagamento</th>
                                <th class="text-center">Estado rececao</th>
                                <th class="text-center">PDF encomenda</th>
                                <th class="text-center">Rececao</th>
                                <th class="text-center">Devolucao</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeAward->preparedOrders as $preparedOrder)
                                @php
                                    $orderedQty = $preparedOrder->totalOrderedQty();
                                    $receivedQty = $preparedOrder->totalReceivedQty();
                                    $returnedQty = $preparedOrder->totalReturnedQty();
                                    $netReceivedQty = $preparedOrder->totalNetReceivedQty();
                                    $pendingQty = $preparedOrder->totalPendingQty();
                                    $statusLabel = $preparedOrder->statusLabel();
                                    $statusClass = match ($preparedOrder->status) {
                                        \App\Models\PurchaseSupplierOrder::STATUS_RECEIVED => 'bg-success',
                                        \App\Models\PurchaseSupplierOrder::STATUS_PARTIALLY_RECEIVED => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $preparedOrder->supplier?->code ? $preparedOrder->supplier->code . ' - ' . $preparedOrder->supplier->name : ($preparedOrder->supplier?->name ?: '-') }}</td>
                                    <td class="text-center">{{ $preparedOrder->items->count() }}</td>
                                    <td class="text-end">{{ number_format((float) $orderedQty, 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $receivedQty, 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $returnedQty, 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $netReceivedQty, 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $pendingQty, 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $preparedOrder->subtotal_amount, 2, ',', '.') }}</td>
                                    <td>{{ $preparedOrder->currency }}</td>
                                    <td>{{ $preparedOrder->paymentTerm?->displayLabel() ?: '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchase-requests.supplier-orders.pdf', [$purchaseRequest, $preparedOrder]) }}" target="_blank">
                                            PDF
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        @can('purchases.update')
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('purchase-requests.supplier-orders.receipts.create', [$purchaseRequest, $preparedOrder]) }}">
                                                Registar rececao
                                            </a>
                                        @else
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchase-requests.supplier-orders.receipts.create', [$purchaseRequest, $preparedOrder]) }}">
                                                Ver rececoes
                                            </a>
                                        @endcan
                                    </td>
                                    <td class="text-center">
                                        @can('purchases.update')
                                            <a class="btn btn-sm btn-outline-danger" href="{{ route('purchase-requests.supplier-orders.returns.create', [$purchaseRequest, $preparedOrder]) }}">
                                                Registar devolucao
                                            </a>
                                        @else
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchase-requests.supplier-orders.returns.create', [$purchaseRequest, $preparedOrder]) }}">
                                                Ver devolucoes
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <div class="text-muted">Ainda sem adjudicacao registada para este RFQ.</div>
        @endif
    </div>
</section>

@can('purchases.award')
    @if ($purchaseRequest->isEditable() && $eligibleQuotes->isNotEmpty())
        <div class="modal fade" id="awardLowestTotalModal" tabindex="-1" aria-labelledby="awardLowestTotalModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="awardLowestTotalModalLabel">Adjudicar ao mais barato (global)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <form method="POST" action="{{ route('purchase-requests.award', $purchaseRequest) }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ \App\Models\PurchaseRequestAward::MODE_LOWEST_TOTAL }}">
                        <div class="modal-body">
                            @if ($globalWinner)
                                <div><strong>Fornecedor vencedor:</strong> {{ $globalWinner->supplier_name_snapshot }}</div>
                                <div><strong>Total global s/ IVA:</strong> {{ number_format((float) ($globalWinner->comparison_total_amount ?? $globalWinner->total_amount), 2, ',', '.') }} {{ $globalWinner->currency }}</div>
                                <div><strong>Linhas cotadas:</strong> {{ $awardPreview['global']['quoted_lines_count'] ?? 0 }}</div>
                                <div><strong>Linhas em falta:</strong> {{ $awardPreview['global']['missing_lines_count'] ?? 0 }}</div>
                            @endif

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="allow_partial" value="1" id="allow_partial_global" @checked(old('allow_partial'))>
                                <label class="form-check-label" for="allow_partial_global">Permitir adjudicacao parcial se existirem linhas sem proposta valida</label>
                            </div>

                            @if ($activeAward)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="replace_existing" value="1" id="replace_existing_global" @checked(old('replace_existing'))>
                                    <label class="form-check-label" for="replace_existing_global">Substituir adjudicacao ativa atual</label>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Confirmar adjudicacao</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="awardLowestPerLineModal" tabindex="-1" aria-labelledby="awardLowestPerLineModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="awardLowestPerLineModalLabel">Adjudicar por artigo mais barato</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <form method="POST" action="{{ route('purchase-requests.award', $purchaseRequest) }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ \App\Models\PurchaseRequestAward::MODE_LOWEST_PER_LINE }}">
                        <div class="modal-body">
                            <div><strong>Linhas vencedoras:</strong> {{ $awardPreview['perLine']['winning_lines_count'] ?? 0 }}</div>
                            <div><strong>Linhas sem proposta valida:</strong> {{ $awardPreview['perLine']['missing_lines_count'] ?? 0 }}</div>
                            <div class="small text-muted mb-2">Sera gerada uma encomenda preparada por fornecedor vencedor.</div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead><tr><th>Fornecedor</th><th class="text-center">Linhas</th><th class="text-end">Total adjudicado s/ IVA</th></tr></thead>
                                    <tbody>
                                        @forelse ($perLineBySupplier as $supplierSummary)
                                            <tr>
                                                <td>{{ $supplierSummary['supplier_name'] }}</td>
                                                <td class="text-center">{{ $supplierSummary['lines_count'] }}</td>
                                                <td class="text-end">{{ number_format((float) $supplierSummary['total_amount'], 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-muted">Sem resumo disponivel.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <div class="small text-muted mb-2">Mapa de adjudicacao por linha (pre-visualizacao)</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Artigo</th>
                                                <th class="text-end">Qtd pedida</th>
                                                <th class="text-center">Un.</th>
                                                <th>Fornecedor vencedor</th>
                                                <th class="text-end">Qtd adjudicada</th>
                                                <th class="text-end">Preco unit. s/ IVA</th>
                                                <th class="text-end">Total linha s/ IVA</th>
                                                <th class="text-center">Prazo linha</th>
                                                <th class="text-center">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($perLineItemsMap as $lineMap)
                                                @php($requestItem = $lineMap['request_item'])
                                                @if ($lineMap['is_missing'])
                                                    <tr class="table-danger">
                                                        <td>{{ $requestItem->item?->code ?: 'MANUAL' }}</td>
                                                        <td class="text-end">{{ number_format((float) $requestItem->qty, 3, ',', '.') }}</td>
                                                        <td class="text-center">{{ $requestItem->item?->unit?->code ?: $requestItem->unit_snapshot ?: '-' }}</td>
                                                        <td class="text-muted">Sem proposta</td>
                                                        <td class="text-end">-</td>
                                                        <td class="text-end">-</td>
                                                        <td class="text-end">-</td>
                                                        <td class="text-center">-</td>
                                                        <td class="text-center"><span class="badge bg-danger">Nao cotado</span></td>
                                                    </tr>
                                                @else
                                                    @php($winner = $lineMap['winner'])
                                                    <tr class="{{ $winner['qty_divergent'] ? 'table-warning' : '' }}">
                                                        <td>{{ $requestItem->item?->code ?: 'MANUAL' }}</td>
                                                        <td class="text-end">{{ number_format((float) $winner['requested_qty'], 3, ',', '.') }}</td>
                                                        <td class="text-center">{{ $requestItem->item?->unit?->code ?: $requestItem->unit_snapshot ?: '-' }}</td>
                                                        <td>
                                                            {{ $winner['supplier_name'] }}
                                                            @if (! empty($winner['supplier_item_reference']))
                                                                <div class="small text-muted">Ref: {{ $winner['supplier_item_reference'] }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">{{ number_format((float) $winner['awarded_qty'], 3, ',', '.') }}</td>
                                                        <td class="text-end">{{ number_format((float) $winner['unit_price'], 4, ',', '.') }}</td>
                                                        <td class="text-end">{{ $winner['line_total'] !== null ? number_format((float) $winner['line_total'], 2, ',', '.') : '-' }}</td>
                                                        <td class="text-center">{{ $winner['lead_time_days'] !== null ? $winner['lead_time_days'] . ' dias' : '-' }}</td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success">Vencedor</span>
                                                            @if ($winner['qty_divergent'])
                                                                <span class="badge bg-dark">Qtd divergente</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-muted">Sem mapa de adjudicacao disponivel.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="allow_partial" value="1" id="allow_partial_per_line" @checked(old('allow_partial'))>
                                <label class="form-check-label" for="allow_partial_per_line">Permitir adjudicacao parcial se existirem linhas sem proposta valida</label>
                            </div>

                            @if ($activeAward)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="replace_existing" value="1" id="replace_existing_per_line" @checked(old('replace_existing'))>
                                    <label class="form-check-label" for="replace_existing_per_line">Substituir adjudicacao ativa atual</label>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Confirmar adjudicacao</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="awardManualPartialModal" tabindex="-1" aria-labelledby="awardManualPartialModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="awardManualPartialModalLabel">Encomenda parcial manual</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <form method="POST" action="{{ route('purchase-requests.award', $purchaseRequest) }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ \App\Models\PurchaseRequestAward::MODE_MANUAL_PARTIAL }}">
                        <input type="hidden" name="allow_partial" value="1">
                        <div class="modal-body">
                            <div class="alert alert-info py-2">
                                Seleciona fornecedor e quantidade por linha. O preco, desconto e total da linha sao preenchidos automaticamente pela proposta do fornecedor.
                            </div>

                            @error('manual_lines')
                                <div class="alert alert-danger py-2">{{ $message }}</div>
                            @enderror

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Artigo</th>
                                            <th class="text-end">Qtd pedida</th>
                                            <th class="text-center">Un.</th>
                                            <th>Fornecedor</th>
                                            <th class="text-end">Qtd encomendar</th>
                                            <th class="text-end">Preco unit. s/ IVA</th>
                                            <th class="text-end">% Desc.</th>
                                            <th class="text-end">Total linha s/ IVA</th>
                                            <th>Ref. fornecedor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($manualPartialRows as $manualRow)
                                            <tr
                                                class="manual-award-row"
                                                data-row-index="{{ (int) ($manualRow['index'] ?? 0) }}"
                                                data-requested-qty="{{ number_format((float) ($manualRow['request_item']->qty ?? 0), 3, '.', '') }}"
                                            >
                                                <td>
                                                    <div>{{ $manualRow['request_item']->item?->code ?: 'MANUAL' }}</div>
                                                    <div class="small text-muted">{{ $manualRow['request_item']->description }}</div>
                                                </td>
                                                <td class="text-end">{{ number_format((float) ($manualRow['request_item']->qty ?? 0), 3, ',', '.') }}</td>
                                                <td class="text-center">{{ $manualRow['request_item']->item?->unit?->code ?: ($manualRow['request_item']->unit_snapshot ?: '-') }}</td>
                                                <td>
                                                    <input type="hidden" name="manual_lines[{{ (int) ($manualRow['index'] ?? 0) }}][purchase_request_item_id]" value="{{ $manualRow['request_item']->id }}">
                                                    <select
                                                        name="manual_lines[{{ (int) ($manualRow['index'] ?? 0) }}][supplier_id]"
                                                        class="form-select form-select-sm manual-award-supplier @error('manual_lines.' . (int) ($manualRow['index'] ?? 0) . '.supplier_id') is-invalid @enderror"
                                                    >
                                                        <option value="">Sem fornecedor (nao encomendar)</option>
                                                        @foreach ($manualRow['supplier_options'] as $option)
                                                            <option
                                                                value="{{ $option['supplier_id'] }}"
                                                                data-unit-price="{{ number_format((float) $option['unit_price'], 4, '.', '') }}"
                                                                data-discount-percent="{{ number_format((float) $option['discount_percent'], 3, '.', '') }}"
                                                                data-quoted-max="{{ number_format((float) $option['max_qty'], 3, '.', '') }}"
                                                                data-reference="{{ e((string) ($option['supplier_item_reference'] ?? '')) }}"
                                                                @selected(((int) ($manualRow['selected_supplier_id'] ?? 0)) === (int) $option['supplier_id'])
                                                            >
                                                                {{ $option['supplier_code'] ? $option['supplier_code'] . ' - ' . $option['supplier_name'] : $option['supplier_name'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('manual_lines.' . (int) ($manualRow['index'] ?? 0) . '.supplier_id')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        step="0.001"
                                                        min="0"
                                                        max="{{ number_format((float) ($manualRow['request_item']->qty ?? 0), 3, '.', '') }}"
                                                        name="manual_lines[{{ (int) ($manualRow['index'] ?? 0) }}][awarded_qty]"
                                                        class="form-control form-control-sm text-end manual-award-qty @error('manual_lines.' . (int) ($manualRow['index'] ?? 0) . '.awarded_qty') is-invalid @enderror"
                                                        value="{{ old('manual_lines.' . (int) ($manualRow['index'] ?? 0) . '.awarded_qty', number_format((float) ($manualRow['awarded_qty'] ?? 0), 3, '.', '')) }}"
                                                    >
                                                    @error('manual_lines.' . (int) ($manualRow['index'] ?? 0) . '.awarded_qty')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        class="form-control form-control-sm text-end manual-award-unit-price"
                                                        value=""
                                                        readonly
                                                    >
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        class="form-control form-control-sm text-end manual-award-discount"
                                                        value=""
                                                        readonly
                                                    >
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        class="form-control form-control-sm text-end manual-award-total"
                                                        value=""
                                                        readonly
                                                    >
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        class="form-control form-control-sm manual-award-reference"
                                                        value=""
                                                        readonly
                                                    >
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="small text-muted mt-2">
                                Dica: para deixar uma linha fora desta encomenda parcial, deixa o fornecedor vazio ou quantidade 0.
                            </div>

                            @if ($activeAward)
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" name="replace_existing" value="1" id="replace_existing_manual_partial" @checked(old('replace_existing'))>
                                    <label class="form-check-label" for="replace_existing_manual_partial">Substituir adjudicacao ativa atual</label>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Confirmar encomenda parcial</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="awardForcedSupplierModal" tabindex="-1" aria-labelledby="awardForcedSupplierModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="awardForcedSupplierModalLabel">Forcar adjudicacao a fornecedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <form method="POST" action="{{ route('purchase-requests.award', $purchaseRequest) }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ \App\Models\PurchaseRequestAward::MODE_FORCED_SUPPLIER }}">
                        <div class="modal-body">
                            <div class="mb-2">
                                <label for="forced_supplier_id" class="form-label">Fornecedor</label>
                                <select name="forced_supplier_id" id="forced_supplier_id" class="form-select @error('forced_supplier_id') is-invalid @enderror" required>
                                    <option value="">Selecionar...</option>
                                    @foreach ($forcedQuoteOptions as $option)
                                        <option value="{{ $option['supplier_id'] }}" data-total="{{ $option['total_amount'] }}" data-lines="{{ $option['lines_count'] }}" data-currency="{{ $option['currency'] }}" @selected((int) old('forced_supplier_id', 0) === (int) $option['supplier_id'])>
                                            {{ $option['supplier_code'] ? $option['supplier_code'] . ' - ' . $option['supplier_name'] : $option['supplier_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('forced_supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="small text-muted mb-3" id="forced_supplier_summary">Seleciona fornecedor para ver resumo.</div>

                            <div class="mb-2">
                                <label for="forced_justification" class="form-label">Justificacao (obrigatoria)</label>
                                <textarea name="justification" id="forced_justification" rows="4" class="form-control @error('justification') is-invalid @enderror" required>{{ old('justification') }}</textarea>
                                @error('justification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="allow_partial" value="1" id="allow_partial_forced" @checked(old('allow_partial'))>
                                <label class="form-check-label" for="allow_partial_forced">Permitir adjudicacao parcial se existirem linhas sem proposta valida</label>
                            </div>

                            @if ($activeAward)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="replace_existing" value="1" id="replace_existing_forced" @checked(old('replace_existing'))>
                                    <label class="form-check-label" for="replace_existing_forced">Substituir adjudicacao ativa atual</label>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning">Confirmar adjudicacao forcada</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan

@if ($activeAward)
    @can('purchases.update')
        <div class="modal fade" id="sendAwardEmailModal" tabindex="-1" aria-labelledby="sendAwardEmailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendAwardEmailModalLabel">Enviar adjudicacao por email</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <form method="POST" action="{{ route('purchase-requests.awards.send-email', [$purchaseRequest, $activeAward]) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="award_supplier_id" class="form-label">Fornecedor vencedor (opcional)</label>
                                    <select name="award_supplier_id" id="award_supplier_id" class="form-select">
                                        <option value="">Todos (resumo global)</option>
                                        @foreach ($awardEmailSuppliers as $awardSupplier)
                                            <option
                                                value="{{ $awardSupplier['id'] }}"
                                                data-name="{{ $awardSupplier['recipient_name'] }}"
                                                data-email="{{ $awardSupplier['recipient_email'] }}"
                                                @selected((int) old('award_supplier_id', 0) === (int) $awardSupplier['id'])
                                            >
                                                {{ $awardSupplier['code'] ? $awardSupplier['code'] . ' - ' . $awardSupplier['name'] : $awardSupplier['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="award_recipient_name" class="form-label">Nome do destinatario</label>
                                    <input type="text" name="award_recipient_name" id="award_recipient_name" class="form-control @error('award_recipient_name') is-invalid @enderror" value="{{ old('award_recipient_name') }}" maxlength="150">
                                    @error('award_recipient_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="award_recipient_email" class="form-label">Email do destinatario</label>
                                    <input type="email" name="award_recipient_email" id="award_recipient_email" class="form-control @error('award_recipient_email') is-invalid @enderror" value="{{ old('award_recipient_email') }}" maxlength="150" required>
                                    @error('award_recipient_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label for="award_cc_email" class="form-label">CC</label>
                                    <input type="email" name="award_cc_email" id="award_cc_email" class="form-control @error('award_cc_email') is-invalid @enderror" value="{{ old('award_cc_email') }}" maxlength="150">
                                    @error('award_cc_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label for="award_bcc_email" class="form-label">BCC</label>
                                    <input type="email" name="award_bcc_email" id="award_bcc_email" class="form-control @error('award_bcc_email') is-invalid @enderror" value="{{ old('award_bcc_email') }}" maxlength="150">
                                    @error('award_bcc_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="award_email_notes" class="form-label">Observacoes no email</label>
                                    <textarea name="award_email_notes" id="award_email_notes" rows="4" class="form-control @error('award_email_notes') is-invalid @enderror" placeholder="Mensagem opcional">{{ old('award_email_notes') }}</textarea>
                                    @error('award_email_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="award_email_attachment" class="form-label">Anexo adicional (opcional)</label>
                                    <input type="file" name="award_email_attachment" id="award_email_attachment" class="form-control @error('award_email_attachment') is-invalid @enderror">
                                    @error('award_email_attachment')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        O PDF da adjudicacao sera anexado automaticamente ao email.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Confirmar envio</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif

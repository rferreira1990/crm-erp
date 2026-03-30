@php
    $activeAward = $awardPreview['activeAward'] ?? null;
    $eligibleQuotes = collect($awardPreview['eligibleQuotes'] ?? []);
    $globalWinner = $awardPreview['global']['winnerQuote'] ?? null;
    $perLineBySupplier = collect($awardPreview['perLine']['bySupplier'] ?? []);
    $perLineItemsMap = collect($awardPreview['perLine']['itemsMap'] ?? []);
    $forcedQuoteOptions = $eligibleQuotes->map(function ($quote) {
        return [
            'supplier_id' => (int) $quote->supplier_id,
            'supplier_name' => $quote->supplier_name_snapshot,
            'supplier_code' => $quote->supplier?->code,
            'quote_id' => (int) $quote->id,
            'total_amount' => (float) $quote->total_amount,
            'lines_count' => (int) $quote->items->whereNotNull('unit_price')->count(),
            'currency' => $quote->currency,
        ];
    })->unique('supplier_id')->values();
@endphp

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Adjudicacao e encomendas preparadas</h3>
        @can('purchases.award')
            @if ($purchaseRequest->isEditable() && $eligibleQuotes->isNotEmpty())
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#awardLowestTotalModal">Adjudicar ao mais barato (global)</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#awardLowestPerLineModal">Adjudicar por artigo mais barato</button>
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
            </div>

            @if ($activeAward->preparedOrders->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fornecedor</th>
                                <th class="text-center">Linhas</th>
                                <th class="text-end">Subtotal s/ IVA</th>
                                <th>Moeda</th>
                                <th>Cond. pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeAward->preparedOrders as $preparedOrder)
                                <tr>
                                    <td>{{ $preparedOrder->supplier?->code ? $preparedOrder->supplier->code . ' - ' . $preparedOrder->supplier->name : ($preparedOrder->supplier?->name ?: '-') }}</td>
                                    <td class="text-center">{{ $preparedOrder->items->count() }}</td>
                                    <td class="text-end">{{ number_format((float) $preparedOrder->subtotal_amount, 2, ',', '.') }}</td>
                                    <td>{{ $preparedOrder->currency }}</td>
                                    <td>{{ $preparedOrder->paymentTerm?->displayLabel() ?: '-' }}</td>
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
                                <div><strong>Total global s/ IVA:</strong> {{ number_format((float) $globalWinner->total_amount, 2, ',', '.') }} {{ $globalWinner->currency }}</div>
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

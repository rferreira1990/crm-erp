@php
    $quoteItemsByRequestItemId = $quoteItemsByRequestItemId ?? collect();
    $useOldValues = $useOldValues ?? false;
    $formPrefix = $formPrefix ?? 'quote-form';
@endphp

<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle quote-lines-table" data-form-prefix="{{ $formPrefix }}">
        <thead>
            <tr>
                <th>Linha RFQ</th>
                <th>Descricao</th>
                <th class="text-end">Qtd pedida</th>
                <th class="text-center">Un.</th>
                <th>Ref. fornecedor</th>
                <th class="text-end">Qtd cotada</th>
                <th class="text-end">Preco unit. s/ IVA</th>
                <th class="text-end">Desc %</th>
                <th class="text-end">Total linha s/ IVA</th>
                <th>Observacoes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseRequest->items as $requestLine)
                @php
                    $quoteLine = $quoteItemsByRequestItemId->get($requestLine->id);
                    $defaultQuotedQty = $useOldValues
                        ? old('items.' . $requestLine->id . '.quoted_qty', number_format((float) $requestLine->qty, 3, '.', ''))
                        : ($quoteLine?->quoted_qty !== null ? number_format((float) $quoteLine->quoted_qty, 3, '.', '') : '');
                    $supplierItemReference = $useOldValues
                        ? old('items.' . $requestLine->id . '.supplier_item_reference')
                        : ($quoteLine?->supplier_item_reference ?? '');
                    $unitPrice = $useOldValues
                        ? old('items.' . $requestLine->id . '.unit_price')
                        : ($quoteLine?->unit_price !== null ? number_format((float) $quoteLine->unit_price, 4, '.', '') : '');
                    $discountPercent = $useOldValues
                        ? old('items.' . $requestLine->id . '.discount_percent')
                        : ($quoteLine?->discount_percent !== null ? number_format((float) $quoteLine->discount_percent, 3, '.', '') : '');
                    $lineTotal = $quoteLine?->line_total !== null ? number_format((float) $quoteLine->line_total, 2, '.', '') : '';
                    $lineNotes = $useOldValues
                        ? old('items.' . $requestLine->id . '.notes')
                        : ($quoteLine?->notes ?? '');
                    $articleCode = $requestLine->item?->code ?: 'MANUAL';
                    $unitCode = $requestLine->item?->unit?->code ?: ($requestLine->unit_snapshot ?: '-');
                @endphp
                <tr class="quote-line-row" data-item-id="{{ (int) $requestLine->item_id }}">
                    <td>
                        <input type="hidden" name="items[{{ $requestLine->id }}][purchase_request_item_id]" value="{{ $requestLine->id }}">
                        <div class="fw-semibold">#{{ $requestLine->sort_order }} {{ $articleCode }}</div>
                    </td>
                    <td>{{ $requestLine->description }}</td>
                    <td class="text-end">{{ number_format((float) $requestLine->qty, 3, ',', '.') }}</td>
                    <td class="text-center">{{ $unitCode }}</td>
                    <td>
                        <input
                            type="text"
                            maxlength="120"
                            name="items[{{ $requestLine->id }}][supplier_item_reference]"
                            class="form-control form-control-sm supplier-item-reference-input"
                            value="{{ $supplierItemReference }}"
                            placeholder="Ref. do fornecedor"
                        >
                    </td>
                    <td>
                        <input
                            type="number"
                            step="0.001"
                            min="0.001"
                            name="items[{{ $requestLine->id }}][quoted_qty]"
                            class="form-control form-control-sm text-end quoted-qty-input"
                            value="{{ $defaultQuotedQty }}"
                        >
                    </td>
                    <td>
                        <input
                            type="number"
                            step="0.0001"
                            min="0"
                            name="items[{{ $requestLine->id }}][unit_price]"
                            class="form-control form-control-sm text-end unit-price-input"
                            value="{{ $unitPrice }}"
                        >
                    </td>
                    <td>
                        <input
                            type="number"
                            step="0.001"
                            min="0"
                            max="100"
                            name="items[{{ $requestLine->id }}][discount_percent]"
                            class="form-control form-control-sm text-end discount-percent-input"
                            value="{{ $discountPercent }}"
                        >
                    </td>
                    <td>
                        <input
                            type="text"
                            class="form-control form-control-sm text-end line-total-display"
                            value="{{ $lineTotal }}"
                            readonly
                            tabindex="-1"
                        >
                    </td>
                    <td>
                        <input
                            type="text"
                            maxlength="2000"
                            name="items[{{ $requestLine->id }}][notes]"
                            class="form-control form-control-sm"
                            value="{{ $lineNotes }}"
                        >
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>


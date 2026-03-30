@php
    $quoteItemsByRequestItemId = $quoteItemsByRequestItemId ?? collect();
    $useOldValues = $useOldValues ?? false;
@endphp

<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle">
        <thead>
            <tr>
                <th>Linha RFQ</th>
                <th class="text-end">Qtd pedida</th>
                <th class="text-end">Qtd cotada</th>
                <th class="text-end">Preco unit.</th>
                <th class="text-end">Desc %</th>
                <th class="text-end">Total linha</th>
                <th class="text-center">Prazo</th>
                <th>Observacao</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseRequest->items as $requestLine)
                @php
                    $quoteLine = $quoteItemsByRequestItemId->get($requestLine->id);
                    $quotedQty = $useOldValues
                        ? old('items.' . $requestLine->id . '.quoted_qty')
                        : ($quoteLine?->quoted_qty !== null ? number_format((float) $quoteLine->quoted_qty, 3, '.', '') : '');
                    $unitPrice = $useOldValues
                        ? old('items.' . $requestLine->id . '.unit_price')
                        : ($quoteLine?->unit_price !== null ? number_format((float) $quoteLine->unit_price, 4, '.', '') : '');
                    $discountPercent = $useOldValues
                        ? old('items.' . $requestLine->id . '.discount_percent')
                        : ($quoteLine?->discount_percent !== null ? number_format((float) $quoteLine->discount_percent, 3, '.', '') : '');
                    $lineTotal = $useOldValues
                        ? old('items.' . $requestLine->id . '.line_total')
                        : ($quoteLine?->line_total !== null ? number_format((float) $quoteLine->line_total, 2, '.', '') : '');
                    $lineLeadTime = $useOldValues
                        ? old('items.' . $requestLine->id . '.lead_time_days')
                        : ($quoteLine?->lead_time_days ?? '');
                    $lineNotes = $useOldValues
                        ? old('items.' . $requestLine->id . '.notes')
                        : ($quoteLine?->notes ?? '');
                @endphp
                <tr>
                    <td>
                        <input type="hidden" name="items[{{ $requestLine->id }}][purchase_request_item_id]" value="{{ $requestLine->id }}">
                        <div class="fw-semibold">#{{ $requestLine->sort_order }} {{ $requestLine->item?->code ?: 'Manual' }}</div>
                        <div class="small text-muted">{{ $requestLine->description }}</div>
                    </td>
                    <td class="text-end">{{ number_format((float) $requestLine->qty, 3, ',', '.') }}</td>
                    <td><input type="number" step="0.001" min="0.001" name="items[{{ $requestLine->id }}][quoted_qty]" class="form-control form-control-sm text-end" value="{{ $quotedQty }}"></td>
                    <td><input type="number" step="0.0001" min="0" name="items[{{ $requestLine->id }}][unit_price]" class="form-control form-control-sm text-end" value="{{ $unitPrice }}"></td>
                    <td><input type="number" step="0.001" min="0" max="100" name="items[{{ $requestLine->id }}][discount_percent]" class="form-control form-control-sm text-end" value="{{ $discountPercent }}"></td>
                    <td><input type="number" step="0.01" min="0" name="items[{{ $requestLine->id }}][line_total]" class="form-control form-control-sm text-end" value="{{ $lineTotal }}"></td>
                    <td><input type="number" min="0" name="items[{{ $requestLine->id }}][lead_time_days]" class="form-control form-control-sm text-center" value="{{ $lineLeadTime }}"></td>
                    <td><input type="text" maxlength="2000" name="items[{{ $requestLine->id }}][notes]" class="form-control form-control-sm" value="{{ $lineNotes }}"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<?php

namespace App\Services\Purchases;

use App\Models\PurchaseQuote;
use App\Models\PurchaseQuoteItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAward;
use App\Models\PurchaseSupplierOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequestAwardService
{
    /**
     * @return array<string, mixed>
     */
    public function buildPreview(PurchaseRequest $purchaseRequest): array
    {
        $purchaseRequest->loadMissing([
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name,code',
            'quotes.items.requestItem:id,qty',
            'quotes.supplier:id,name,code',
            'activeAward.decidedBy:id,name',
            'activeAward.forcedSupplier:id,name,code',
            'activeAward.items',
            'activeAward.preparedOrders.items',
        ]);

        $eligibleQuotes = $this->eligibleQuotes($purchaseRequest->quotes);
        $requestItemIds = $purchaseRequest->items->pluck('id')->map(fn ($id) => (int) $id)->all();

        $globalWinner = $this->resolveGlobalWinner($eligibleQuotes);
        $globalQuotedIds = $globalWinner
            ? $globalWinner->items->whereNotNull('unit_price')->pluck('purchase_request_item_id')->map(fn ($id) => (int) $id)->all()
            : [];
        $globalMissingIds = array_values(array_diff($requestItemIds, $globalQuotedIds));

        $perLineDecision = $this->resolveLowestPerLine($purchaseRequest, $eligibleQuotes);
        $decisionItemsByRequestItemId = collect($perLineDecision['items'])
            ->keyBy(fn (array $item) => (int) ($item['purchase_request_item_id'] ?? 0));
        $quotesById = $eligibleQuotes->keyBy('id');

        $perLineItemsMap = $purchaseRequest->items
            ->sortBy(fn ($item) => [(int) $item->sort_order, (int) $item->id])
            ->values()
            ->map(function ($requestItem) use ($decisionItemsByRequestItemId, $quotesById) {
                $decisionItem = $decisionItemsByRequestItemId->get((int) $requestItem->id);

                if (! $decisionItem) {
                    return [
                        'request_item' => $requestItem,
                        'is_missing' => true,
                        'winner' => null,
                    ];
                }

                /** @var PurchaseQuote|null $winnerQuote */
                $winnerQuote = $quotesById->get((int) ($decisionItem['purchase_quote_id'] ?? 0));
                /** @var PurchaseQuoteItem|null $winnerQuoteItem */
                $winnerQuoteItem = $winnerQuote?->items
                    ?->firstWhere('purchase_request_item_id', (int) $requestItem->id);

                $requestedQty = (float) $requestItem->qty;
                $awardedQty = (float) ($decisionItem['awarded_qty'] ?? 0);

                return [
                    'request_item' => $requestItem,
                    'is_missing' => false,
                    'winner' => [
                        'supplier_id' => (int) ($decisionItem['supplier_id'] ?? 0),
                        'supplier_name' => $winnerQuote?->supplier_name_snapshot ?: '-',
                        'quote_id' => (int) ($decisionItem['purchase_quote_id'] ?? 0),
                        'supplier_item_reference' => $decisionItem['supplier_item_reference'] ?? null,
                        'requested_qty' => $requestedQty,
                        'awarded_qty' => $awardedQty,
                        'qty_divergent' => abs($awardedQty - $requestedQty) > 0.0005,
                        'unit_price' => (float) ($decisionItem['unit_price'] ?? 0),
                        'discount_percent' => $decisionItem['discount_percent'] !== null
                            ? (float) $decisionItem['discount_percent']
                            : null,
                        'line_total' => $decisionItem['line_total'] !== null
                            ? (float) $decisionItem['line_total']
                            : null,
                        'lead_time_days' => $winnerQuoteItem?->lead_time_days,
                        'notes' => $decisionItem['notes'] ?? null,
                    ],
                ];
            });

        return [
            'eligibleQuotes' => $eligibleQuotes,
            'global' => [
                'winnerQuote' => $globalWinner,
                'quoted_lines_count' => count($globalQuotedIds),
                'missing_lines_count' => count($globalMissingIds),
            ],
            'perLine' => [
                'bySupplier' => $perLineDecision['summary_by_supplier'],
                'itemsMap' => $perLineItemsMap,
                'winning_lines_count' => count($perLineDecision['items']),
                'missing_lines_count' => count($perLineDecision['missing_item_ids']),
            ],
            'activeAward' => $purchaseRequest->activeAward,
            'canAward' => $eligibleQuotes->isNotEmpty(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function award(PurchaseRequest $purchaseRequest, User $user, array $data): PurchaseRequestAward
    {
        $purchaseRequest->loadMissing([
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name,code',
            'quotes.items.requestItem:id,qty',
            'quotes.supplier:id,name,code',
            'activeAward',
        ]);

        $eligibleQuotes = $this->eligibleQuotes($purchaseRequest->quotes);

        if ($eligibleQuotes->isEmpty()) {
            throw ValidationException::withMessages([
                'mode' => 'Nao existem propostas validas para adjudicar.',
            ]);
        }

        $activeAward = $purchaseRequest->activeAward;
        $replaceExisting = (bool) ($data['replace_existing'] ?? false);

        if ($activeAward && ! $replaceExisting) {
            throw ValidationException::withMessages([
                'replace_existing' => 'Ja existe adjudicacao ativa. Confirma substituicao para continuar.',
            ]);
        }

        $mode = (string) $data['mode'];
        $allowPartial = (bool) ($data['allow_partial'] ?? false);
        $justification = trim((string) ($data['justification'] ?? ''));
        $forcedSupplierId = ! empty($data['forced_supplier_id']) ? (int) $data['forced_supplier_id'] : null;

        $decision = match ($mode) {
            PurchaseRequestAward::MODE_LOWEST_TOTAL => $this->resolveLowestTotal($purchaseRequest, $eligibleQuotes),
            PurchaseRequestAward::MODE_LOWEST_PER_LINE => $this->resolveLowestPerLine($purchaseRequest, $eligibleQuotes),
            PurchaseRequestAward::MODE_FORCED_SUPPLIER => $this->resolveForcedSupplier(
                purchaseRequest: $purchaseRequest,
                eligibleQuotes: $eligibleQuotes,
                forcedSupplierId: $forcedSupplierId,
                justification: $justification
            ),
            default => throw ValidationException::withMessages(['mode' => 'Modo de adjudicacao invalido.']),
        };

        if (count($decision['items']) === 0) {
            throw ValidationException::withMessages([
                'mode' => 'Nao foi encontrada nenhuma linha valida para adjudicar.',
            ]);
        }

        if (! $allowPartial && count($decision['missing_item_ids']) > 0) {
            throw ValidationException::withMessages([
                'allow_partial' => 'Existem linhas sem proposta valida. Ativa adjudicacao parcial para continuar.',
            ]);
        }

        $quotesById = $eligibleQuotes->keyBy('id');
        $requestItemsById = $purchaseRequest->items->keyBy('id');

        /** @var PurchaseRequestAward $award */
        $award = DB::transaction(function () use (
            $purchaseRequest,
            $user,
            $activeAward,
            $decision,
            $mode,
            $forcedSupplierId,
            $justification,
            $allowPartial,
            $quotesById,
            $requestItemsById
        ) {
            $award = PurchaseRequestAward::query()->create([
                'purchase_request_id' => $purchaseRequest->id,
                'mode' => $mode,
                'forced_supplier_id' => $forcedSupplierId,
                'selected_quote_id' => $decision['selected_quote_id'],
                'justification' => $justification !== '' ? $justification : null,
                'allow_partial' => $allowPartial,
                'status' => PurchaseRequestAward::STATUS_ACTIVE,
                'decision_payload' => $decision['payload'],
                'generated_orders_count' => 0,
                'generated_items_count' => 0,
                'decided_at' => now(),
                'decided_by' => $user->id,
            ]);

            foreach ($decision['items'] as $itemData) {
                $award->items()->create($itemData);
            }

            $itemsBySupplier = collect($decision['items'])->groupBy(fn (array $item) => (int) $item['supplier_id']);
            $generatedOrdersCount = 0;
            $generatedItemsCount = 0;

            foreach ($itemsBySupplier as $supplierId => $supplierItems) {
                $firstItem = $supplierItems->first();
                $quoteId = (int) ($firstItem['purchase_quote_id'] ?? 0);
                $quote = $quotesById->get($quoteId);

                $order = PurchaseSupplierOrder::query()->create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'award_id' => $award->id,
                    'supplier_id' => (int) $supplierId,
                    'purchase_quote_id' => $quote?->id,
                    'payment_term_id' => $quote?->payment_term_id,
                    'currency' => $quote?->currency ?: 'EUR',
                    'status' => PurchaseSupplierOrder::STATUS_PREPARED,
                    'subtotal_amount' => round((float) $supplierItems->sum(fn (array $item) => (float) ($item['line_total'] ?? 0)), 2),
                    'notes' => 'Encomenda preparada automaticamente a partir do RFQ ' . $purchaseRequest->code,
                    'prepared_at' => now(),
                    'prepared_by' => $user->id,
                ]);

                $sortOrder = 1;

                foreach ($supplierItems as $supplierItem) {
                    $requestItemId = (int) $supplierItem['purchase_request_item_id'];
                    $requestItem = $requestItemsById->get($requestItemId);
                    if (! $requestItem) {
                        continue;
                    }

                    $order->items()->create([
                        'purchase_request_item_id' => $requestItemId,
                        'purchase_quote_item_id' => $supplierItem['purchase_quote_item_id'] ?? null,
                        'item_id' => $requestItem->item_id,
                        'description' => $requestItem->description,
                        'unit_snapshot' => $requestItem->item?->unit?->code ?: $requestItem->unit_snapshot,
                        'supplier_item_reference' => $supplierItem['supplier_item_reference'] ?? null,
                        'qty' => $supplierItem['awarded_qty'],
                        'unit_price' => $supplierItem['unit_price'],
                        'discount_percent' => $supplierItem['discount_percent'],
                        'line_total' => $supplierItem['line_total'],
                        'notes' => $supplierItem['notes'] ?? null,
                        'sort_order' => $sortOrder++,
                    ]);

                    $generatedItemsCount++;
                }

                $generatedOrdersCount++;
            }

            $award->update([
                'generated_orders_count' => $generatedOrdersCount,
                'generated_items_count' => $generatedItemsCount,
            ]);

            if ($activeAward) {
                $activeAward->update([
                    'status' => PurchaseRequestAward::STATUS_REPLACED,
                    'replaced_by_award_id' => $award->id,
                ]);
            }

            $purchaseRequest->update([
                'status' => PurchaseRequest::STATUS_CLOSED,
                'updated_by' => $user->id,
            ]);

            if ($mode === PurchaseRequestAward::MODE_LOWEST_TOTAL || $mode === PurchaseRequestAward::MODE_FORCED_SUPPLIER) {
                $selectedQuoteId = (int) ($decision['selected_quote_id'] ?? 0);

                if ($selectedQuoteId > 0) {
                    PurchaseQuote::query()
                        ->where('purchase_request_id', $purchaseRequest->id)
                        ->whereKeyNot($selectedQuoteId)
                        ->where('status', PurchaseQuote::STATUS_SELECTED)
                        ->update([
                            'status' => PurchaseQuote::STATUS_RECEIVED,
                            'updated_by' => $user->id,
                        ]);

                    PurchaseQuote::query()
                        ->whereKey($selectedQuoteId)
                        ->update([
                            'status' => PurchaseQuote::STATUS_SELECTED,
                            'updated_by' => $user->id,
                        ]);
                }
            } else {
                PurchaseQuote::query()
                    ->where('purchase_request_id', $purchaseRequest->id)
                    ->where('status', PurchaseQuote::STATUS_SELECTED)
                    ->update([
                        'status' => PurchaseQuote::STATUS_RECEIVED,
                        'updated_by' => $user->id,
                    ]);
            }

            return $award;
        });

        return $award->fresh([
            'items.supplier:id,name,code',
            'preparedOrders.items',
            'decidedBy:id,name',
            'forcedSupplier:id,name,code',
            'selectedQuote:id,supplier_id,total_amount,currency',
        ]) ?? $award;
    }

    /**
     * @param Collection<int, PurchaseQuote> $quotes
     * @return Collection<int, PurchaseQuote>
     */
    private function eligibleQuotes(Collection $quotes): Collection
    {
        return $quotes
            ->filter(fn (PurchaseQuote $quote) => in_array($quote->status, [
                PurchaseQuote::STATUS_RECEIVED,
                PurchaseQuote::STATUS_SELECTED,
            ], true))
            ->values();
    }

    /**
     * @param Collection<int, PurchaseQuote> $eligibleQuotes
     */
    private function resolveGlobalWinner(Collection $eligibleQuotes): ?PurchaseQuote
    {
        return $eligibleQuotes
            ->sortBy(function (PurchaseQuote $quote) {
                $lead = $quote->lead_time_days ?? 999999;

                return [(float) $quote->total_amount, (int) $lead, (int) $quote->id];
            })
            ->first();
    }

    /**
     * @param Collection<int, PurchaseQuote> $eligibleQuotes
     * @return array{
     *   selected_quote_id:int|null,
     *   items:array<int, array<string, mixed>>,
     *   missing_item_ids:array<int, int>,
     *   payload:array<string, mixed>
     * }
     */
    private function resolveLowestTotal(PurchaseRequest $purchaseRequest, Collection $eligibleQuotes): array
    {
        $winner = $this->resolveGlobalWinner($eligibleQuotes);

        if (! $winner) {
            throw ValidationException::withMessages([
                'mode' => 'Nao foi possivel determinar vencedor global.',
            ]);
        }

        $quotedItems = $winner->items
            ->whereNotNull('unit_price')
            ->keyBy('purchase_request_item_id');

        $awardItems = [];

        foreach ($quotedItems as $requestItemId => $quoteItem) {
            /** @var PurchaseQuoteItem $quoteItem */
            $awardItems[] = $this->toAwardItemData(
                quoteId: (int) $winner->id,
                quoteItem: $quoteItem,
                supplierId: (int) $winner->supplier_id,
                tieBreakNote: null
            );
        }

        $requestItemIds = $purchaseRequest->items->pluck('id')->map(fn ($id) => (int) $id)->all();
        $missing = array_values(array_diff($requestItemIds, array_map(fn (array $item) => (int) $item['purchase_request_item_id'], $awardItems)));

        return [
            'selected_quote_id' => (int) $winner->id,
            'items' => $awardItems,
            'missing_item_ids' => $missing,
            'payload' => [
                'tie_break_rule' => 'total_amount ASC, lead_time_days ASC, quote_id ASC',
                'winner_quote_id' => (int) $winner->id,
                'winner_supplier_id' => (int) $winner->supplier_id,
                'winner_total_amount' => (float) $winner->total_amount,
            ],
        ];
    }

    /**
     * @param Collection<int, PurchaseQuote> $eligibleQuotes
     * @return array{
     *   selected_quote_id:int|null,
     *   items:array<int, array<string, mixed>>,
     *   missing_item_ids:array<int, int>,
     *   payload:array<string, mixed>,
     *   summary_by_supplier:array<int, array{supplier_id:int,supplier_name:string,lines_count:int,total_amount:float}>
     * }
     */
    private function resolveLowestPerLine(PurchaseRequest $purchaseRequest, Collection $eligibleQuotes): array
    {
        $quoteItemsByQuoteId = [];
        foreach ($eligibleQuotes as $quote) {
            $quoteItemsByQuoteId[(int) $quote->id] = $quote->items->keyBy('purchase_request_item_id');
        }

        $awardItems = [];
        $missingItemIds = [];
        $summaryBySupplier = [];

        foreach ($purchaseRequest->items as $requestItem) {
            $candidates = [];

            foreach ($eligibleQuotes as $quote) {
                /** @var PurchaseQuoteItem|null $quoteItem */
                $quoteItem = $quoteItemsByQuoteId[(int) $quote->id][(int) $requestItem->id] ?? null;

                if (! $quoteItem || $quoteItem->unit_price === null) {
                    continue;
                }

                $candidates[] = [
                    'quote' => $quote,
                    'quote_item' => $quoteItem,
                ];
            }

            if (count($candidates) === 0) {
                $missingItemIds[] = (int) $requestItem->id;
                continue;
            }

            usort($candidates, function (array $a, array $b) {
                /** @var PurchaseQuoteItem $aItem */
                $aItem = $a['quote_item'];
                /** @var PurchaseQuoteItem $bItem */
                $bItem = $b['quote_item'];
                /** @var PurchaseQuote $aQuote */
                $aQuote = $a['quote'];
                /** @var PurchaseQuote $bQuote */
                $bQuote = $b['quote'];

                $leadA = $aItem->lead_time_days ?? 999999;
                $leadB = $bItem->lead_time_days ?? 999999;

                return [(float) $aItem->unit_price, (int) $leadA, (int) $aQuote->id]
                    <=>
                    [(float) $bItem->unit_price, (int) $leadB, (int) $bQuote->id];
            });

            /** @var PurchaseQuote $winnerQuote */
            $winnerQuote = $candidates[0]['quote'];
            /** @var PurchaseQuoteItem $winnerQuoteItem */
            $winnerQuoteItem = $candidates[0]['quote_item'];

            $awardItems[] = $this->toAwardItemData(
                quoteId: (int) $winnerQuote->id,
                quoteItem: $winnerQuoteItem,
                supplierId: (int) $winnerQuote->supplier_id,
                tieBreakNote: null
            );

            $supplierId = (int) $winnerQuote->supplier_id;
            if (! isset($summaryBySupplier[$supplierId])) {
                $summaryBySupplier[$supplierId] = [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $winnerQuote->supplier_name_snapshot,
                    'lines_count' => 0,
                    'total_amount' => 0.0,
                ];
            }

            $summaryBySupplier[$supplierId]['lines_count']++;
            $summaryBySupplier[$supplierId]['total_amount'] += (float) ($winnerQuoteItem->line_total ?? 0);
        }

        foreach ($summaryBySupplier as $supplierId => $row) {
            $summaryBySupplier[$supplierId]['total_amount'] = round((float) $row['total_amount'], 2);
        }

        return [
            'selected_quote_id' => null,
            'items' => $awardItems,
            'missing_item_ids' => $missingItemIds,
            'summary_by_supplier' => $summaryBySupplier,
            'payload' => [
                'tie_break_rule' => 'unit_price ASC, line_lead_time ASC, quote_id ASC',
                'summary_by_supplier' => array_values($summaryBySupplier),
            ],
        ];
    }

    /**
     * @param Collection<int, PurchaseQuote> $eligibleQuotes
     * @return array{
     *   selected_quote_id:int|null,
     *   items:array<int, array<string, mixed>>,
     *   missing_item_ids:array<int, int>,
     *   payload:array<string, mixed>
     * }
     */
    private function resolveForcedSupplier(
        PurchaseRequest $purchaseRequest,
        Collection $eligibleQuotes,
        ?int $forcedSupplierId,
        string $justification
    ): array {
        if (! $forcedSupplierId || $forcedSupplierId <= 0) {
            throw ValidationException::withMessages([
                'forced_supplier_id' => 'Seleciona o fornecedor para adjudicacao forcada.',
            ]);
        }

        if (trim($justification) === '') {
            throw ValidationException::withMessages([
                'justification' => 'A justificacao e obrigatoria na adjudicacao forcada.',
            ]);
        }

        /** @var PurchaseQuote|null $quote */
        $quote = $eligibleQuotes->firstWhere('supplier_id', $forcedSupplierId);

        if (! $quote) {
            throw ValidationException::withMessages([
                'forced_supplier_id' => 'O fornecedor selecionado nao tem proposta valida neste RFQ.',
            ]);
        }

        $quotedItems = $quote->items
            ->whereNotNull('unit_price')
            ->keyBy('purchase_request_item_id');

        $awardItems = [];

        foreach ($quotedItems as $quoteItem) {
            /** @var PurchaseQuoteItem $quoteItem */
            $awardItems[] = $this->toAwardItemData(
                quoteId: (int) $quote->id,
                quoteItem: $quoteItem,
                supplierId: (int) $quote->supplier_id,
                tieBreakNote: 'forcado_por_decisao_manual'
            );
        }

        $requestItemIds = $purchaseRequest->items->pluck('id')->map(fn ($id) => (int) $id)->all();
        $missing = array_values(array_diff($requestItemIds, array_map(fn (array $item) => (int) $item['purchase_request_item_id'], $awardItems)));

        return [
            'selected_quote_id' => (int) $quote->id,
            'items' => $awardItems,
            'missing_item_ids' => $missing,
            'payload' => [
                'forced_supplier_id' => $forcedSupplierId,
                'forced_quote_id' => (int) $quote->id,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toAwardItemData(
        int $quoteId,
        PurchaseQuoteItem $quoteItem,
        int $supplierId,
        ?string $tieBreakNote
    ): array {
        return [
            'purchase_request_item_id' => (int) $quoteItem->purchase_request_item_id,
            'supplier_id' => $supplierId,
            'purchase_quote_id' => $quoteId,
            'purchase_quote_item_id' => (int) $quoteItem->id,
            'awarded_qty' => $quoteItem->quoted_qty !== null
                ? (float) $quoteItem->quoted_qty
                : (float) ($quoteItem->requestItem?->qty ?? 0),
            'unit_price' => (float) $quoteItem->unit_price,
            'discount_percent' => $quoteItem->discount_percent !== null ? (float) $quoteItem->discount_percent : null,
            'line_total' => $quoteItem->line_total !== null ? (float) $quoteItem->line_total : null,
            'supplier_item_reference' => $quoteItem->supplier_item_reference,
            'notes' => $quoteItem->notes,
            'tie_break_note' => $tieBreakNote,
        ];
    }
}

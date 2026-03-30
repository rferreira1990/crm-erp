<?php

namespace App\Services\Purchases;

use App\Models\PurchaseQuote;
use App\Models\PurchaseQuoteItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Collection;

class RfqComparisonService
{
    /**
     * @return array{
     *   quotes: Collection<int, PurchaseQuote>,
     *   rows: Collection<int, array<string, mixed>>,
     *   summaryByQuoteId: array<int, array{quoted_lines_count:int,missing_lines_count:int}>,
     *   bestPriceQuoteId: int|null,
     *   bestLeadQuoteId: int|null,
     *   selectedQuoteId: int|null
     * }
     */
    public function build(PurchaseRequest $purchaseRequest): array
    {
        $purchaseRequest->loadMissing([
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name',
            'quotes.items',
            'quotes.supplier:id,name,code,email',
        ]);

        $quotes = $purchaseRequest->quotes
            ->sortBy(function (PurchaseQuote $quote) {
                $leadTime = $quote->lead_time_days ?? 999999;

                return [(float) $quote->total_amount, (int) $leadTime, $quote->id];
            })
            ->values();

        $quoteItemsByQuoteId = [];

        foreach ($quotes as $quote) {
            $quoteItemsByQuoteId[(int) $quote->id] = $quote->items->keyBy('purchase_request_item_id');
        }

        $summaryByQuoteId = [];

        foreach ($quotes as $quote) {
            $summaryByQuoteId[(int) $quote->id] = [
                'quoted_lines_count' => 0,
                'missing_lines_count' => 0,
            ];
        }

        $rows = $purchaseRequest->items
            ->sortBy(fn (PurchaseRequestItem $item) => [(int) $item->sort_order, (int) $item->id])
            ->values()
            ->map(function (PurchaseRequestItem $requestItem) use (&$summaryByQuoteId, $quotes, $quoteItemsByQuoteId) {
                $bestUnitPrice = null;
                $bestLeadTime = null;

                foreach ($quotes as $quote) {
                    /** @var PurchaseQuoteItem|null $quoteItem */
                    $quoteItem = $quoteItemsByQuoteId[(int) $quote->id][(int) $requestItem->id] ?? null;

                    if (! $quoteItem) {
                        continue;
                    }

                    if ($quoteItem->unit_price !== null) {
                        $bestUnitPrice = $bestUnitPrice === null
                            ? (float) $quoteItem->unit_price
                            : min($bestUnitPrice, (float) $quoteItem->unit_price);
                    }

                    if ($quoteItem->lead_time_days !== null) {
                        $bestLeadTime = $bestLeadTime === null
                            ? (int) $quoteItem->lead_time_days
                            : min($bestLeadTime, (int) $quoteItem->lead_time_days);
                    }
                }

                $cells = $quotes->map(function (PurchaseQuote $quote) use ($bestLeadTime, $bestUnitPrice, &$summaryByQuoteId, $quoteItemsByQuoteId, $requestItem) {
                    /** @var PurchaseQuoteItem|null $quoteItem */
                    $quoteItem = $quoteItemsByQuoteId[(int) $quote->id][(int) $requestItem->id] ?? null;

                    if (! $quoteItem) {
                        $summaryByQuoteId[(int) $quote->id]['missing_lines_count']++;

                        return [
                            'quote' => $quote,
                            'quote_item' => null,
                            'is_missing' => true,
                            'is_best_price' => false,
                            'is_fastest_lead' => false,
                            'qty_divergent' => false,
                        ];
                    }

                    $summaryByQuoteId[(int) $quote->id]['quoted_lines_count']++;

                    $quotedQty = $quoteItem->quoted_qty !== null
                        ? (float) $quoteItem->quoted_qty
                        : null;

                    $requestedQty = (float) $requestItem->qty;

                    $qtyDivergent = $quotedQty !== null && abs($quotedQty - $requestedQty) > 0.0005;

                    return [
                        'quote' => $quote,
                        'quote_item' => $quoteItem,
                        'is_missing' => false,
                        'is_best_price' => $bestUnitPrice !== null
                            && $quoteItem->unit_price !== null
                            && abs((float) $quoteItem->unit_price - $bestUnitPrice) < 0.00005,
                        'is_fastest_lead' => $bestLeadTime !== null
                            && $quoteItem->lead_time_days !== null
                            && (int) $quoteItem->lead_time_days === (int) $bestLeadTime,
                        'qty_divergent' => $qtyDivergent,
                    ];
                })->values();

                return [
                    'request_item' => $requestItem,
                    'best_unit_price' => $bestUnitPrice,
                    'best_lead_time' => $bestLeadTime,
                    'cells' => $cells,
                ];
            });

        $bestPriceQuoteId = $quotes->first()?->id;
        $bestLeadQuoteId = $quotes
            ->sortBy(fn (PurchaseQuote $quote) => [$quote->lead_time_days ?? 999999, (float) $quote->total_amount, $quote->id])
            ->first()?->id;

        $selectedQuoteId = $quotes->firstWhere('status', PurchaseQuote::STATUS_SELECTED)?->id;

        return [
            'quotes' => $quotes,
            'rows' => $rows,
            'summaryByQuoteId' => $summaryByQuoteId,
            'bestPriceQuoteId' => $bestPriceQuoteId,
            'bestLeadQuoteId' => $bestLeadQuoteId,
            'selectedQuoteId' => $selectedQuoteId,
        ];
    }
}

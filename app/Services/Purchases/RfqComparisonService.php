<?php

namespace App\Services\Purchases;

use App\Models\PurchaseQuote;
use App\Models\PurchaseQuoteItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Collection;

class RfqComparisonService
{
    private const PRICE_TOLERANCE_EUR = 0.01;

    /**
     * @return array{
     *   quotes: Collection<int, PurchaseQuote>,
     *   rows: Collection<int, array<string, mixed>>,
     *   summaryByQuoteId: array<int, array{quoted_lines_count:int,missing_lines_count:int}>,
     *   totalComparisonByQuoteId: array<int, array{delta_percent_vs_best: float|null, best_cheaper_percent: float|null}>,
     *   bestVsSecondTotalPercent: float|null,
     *   bestPriceQuoteId: int|null,
     *   bestLeadQuoteId: int|null,
     *   selectedQuoteId: int|null
     * }
     */
    public function build(PurchaseRequest $purchaseRequest): array
    {
        $purchaseRequest->loadMissing([
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name,code',
            'quotes.items',
            'quotes.supplier:id,name,code,email',
        ]);

        $quotes = $purchaseRequest->quotes
            ->map(function (PurchaseQuote $quote) {
                $quote->setAttribute('comparison_total_amount', $this->resolveQuoteComparableTotal($quote));

                return $quote;
            })
            ->sortBy(function (PurchaseQuote $quote) {
                $leadTime = $quote->lead_time_days ?? 999999;
                $comparisonTotal = (float) ($quote->comparison_total_amount ?? $quote->total_amount);

                return [$comparisonTotal, (int) $leadTime, $quote->id];
            })
            ->values();

        $bestQuote = $quotes->first();
        $bestQuoteId = $bestQuote?->id ? (int) $bestQuote->id : null;
        $bestTotalAmount = $bestQuote ? (float) ($bestQuote->comparison_total_amount ?? $bestQuote->total_amount) : null;

        $secondBestQuote = $quotes->slice(1)->first();
        $secondBestTotalAmount = $secondBestQuote
            ? (float) ($secondBestQuote->comparison_total_amount ?? $secondBestQuote->total_amount)
            : null;

        $bestVsSecondTotalPercent = null;
        if ($bestTotalAmount !== null
            && $secondBestTotalAmount !== null
            && $secondBestTotalAmount > 0
            && ($secondBestTotalAmount - $bestTotalAmount) > self::PRICE_TOLERANCE_EUR
        ) {
            $bestVsSecondTotalPercent = round(
                (($secondBestTotalAmount - $bestTotalAmount) / $secondBestTotalAmount) * 100,
                2
            );
        }

        $totalComparisonByQuoteId = [];
        foreach ($quotes as $quote) {
            $quoteId = (int) $quote->id;
            $comparisonTotal = (float) ($quote->comparison_total_amount ?? $quote->total_amount);

            $deltaPercentVsBest = null;
            $bestCheaperPercent = null;

            if ($bestTotalAmount !== null && $bestTotalAmount > 0) {
                $totalDiff = $comparisonTotal - $bestTotalAmount;

                if ($this->isPriceEquivalent($comparisonTotal, $bestTotalAmount)) {
                    $deltaPercentVsBest = 0.0;
                } else {
                    $deltaPercentVsBest = round(
                        max(0, ($totalDiff / $bestTotalAmount) * 100),
                        2
                    );
                }
            }

            if ($bestTotalAmount !== null && $comparisonTotal > 0 && $comparisonTotal >= $bestTotalAmount) {
                if ($this->isPriceEquivalent($comparisonTotal, $bestTotalAmount)) {
                    $bestCheaperPercent = 0.0;
                } else {
                    $bestCheaperPercent = round(
                        (($comparisonTotal - $bestTotalAmount) / $comparisonTotal) * 100,
                        2
                    );
                }
            }

            if ($bestQuoteId !== null && $quoteId === $bestQuoteId) {
                $deltaPercentVsBest = 0.0;
                $bestCheaperPercent = 0.0;
            }

            $totalComparisonByQuoteId[$quoteId] = [
                'delta_percent_vs_best' => $deltaPercentVsBest,
                'best_cheaper_percent' => $bestCheaperPercent,
            ];
        }

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
                $secondBestUnitPrice = null;

                $rowUnitPrices = [];

                foreach ($quotes as $quote) {
                    /** @var PurchaseQuoteItem|null $quoteItem */
                    $quoteItem = $quoteItemsByQuoteId[(int) $quote->id][(int) $requestItem->id] ?? null;

                    if (! $quoteItem) {
                        continue;
                    }

                    if ($quoteItem->unit_price !== null) {
                        $rowUnitPrices[] = [
                            'quote_id' => (int) $quote->id,
                            'unit_price' => (float) $quoteItem->unit_price,
                        ];

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

                if (! empty($rowUnitPrices)) {
                    usort($rowUnitPrices, function (array $left, array $right): int {
                        $priceComparison = $left['unit_price'] <=> $right['unit_price'];
                        if ($priceComparison !== 0) {
                            return $priceComparison;
                        }

                        return $left['quote_id'] <=> $right['quote_id'];
                    });

                    $secondBestUnitPrice = $rowUnitPrices[1]['unit_price'] ?? null;
                }

                $bestVsSecondUnitPricePercent = null;
                if ($bestUnitPrice !== null
                    && $secondBestUnitPrice !== null
                    && $secondBestUnitPrice > 0
                    && ($secondBestUnitPrice - $bestUnitPrice) > self::PRICE_TOLERANCE_EUR
                ) {
                    $bestVsSecondUnitPricePercent = round(
                        (($secondBestUnitPrice - $bestUnitPrice) / $secondBestUnitPrice) * 100,
                        2
                    );
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
                            'unit_price_diff_percent_vs_best' => null,
                        ];
                    }

                    $summaryByQuoteId[(int) $quote->id]['quoted_lines_count']++;

                    $quotedQty = $quoteItem->quoted_qty !== null
                        ? (float) $quoteItem->quoted_qty
                        : null;

                    $requestedQty = (float) $requestItem->qty;

                    $qtyDivergent = $quotedQty !== null && abs($quotedQty - $requestedQty) > 0.0005;

                    $unitPriceDiffPercentVsBest = null;
                    if ($bestUnitPrice !== null && $quoteItem->unit_price !== null && $bestUnitPrice > 0) {
                        $quoteUnitPrice = (float) $quoteItem->unit_price;

                        if ($this->isPriceEquivalent($quoteUnitPrice, $bestUnitPrice)) {
                            $unitPriceDiffPercentVsBest = 0.0;
                        } else {
                            $unitPriceDiffPercentVsBest = round(
                                max(0, (($quoteUnitPrice - $bestUnitPrice) / $bestUnitPrice) * 100),
                                2
                            );
                        }
                    } elseif ($bestUnitPrice !== null && $quoteItem->unit_price !== null && abs($bestUnitPrice) < 0.0000001) {
                        $unitPriceDiffPercentVsBest = abs((float) $quoteItem->unit_price - $bestUnitPrice) < 0.0000001
                            ? 0.0
                            : null;
                    }

                    return [
                        'quote' => $quote,
                        'quote_item' => $quoteItem,
                        'is_missing' => false,
                        'is_best_price' => $bestUnitPrice !== null
                            && $quoteItem->unit_price !== null
                            && $this->isPriceEquivalent((float) $quoteItem->unit_price, $bestUnitPrice),
                        'is_fastest_lead' => $bestLeadTime !== null
                            && $quoteItem->lead_time_days !== null
                            && (int) $quoteItem->lead_time_days === (int) $bestLeadTime,
                        'qty_divergent' => $qtyDivergent,
                        'unit_price_diff_percent_vs_best' => $unitPriceDiffPercentVsBest,
                    ];
                })->values();

                return [
                    'request_item' => $requestItem,
                    'best_unit_price' => $bestUnitPrice,
                    'second_best_unit_price' => $secondBestUnitPrice,
                    'best_vs_second_unit_price_percent' => $bestVsSecondUnitPricePercent,
                    'best_lead_time' => $bestLeadTime,
                    'cells' => $cells,
                ];
            });

        $bestPriceQuoteId = $quotes->first()?->id;
        $bestLeadQuoteId = $quotes
            ->sortBy(fn (PurchaseQuote $quote) => [
                $quote->lead_time_days ?? 999999,
                (float) ($quote->comparison_total_amount ?? $quote->total_amount),
                $quote->id,
            ])
            ->first()?->id;

        $selectedQuoteId = $quotes->firstWhere('status', PurchaseQuote::STATUS_SELECTED)?->id;

        return [
            'quotes' => $quotes,
            'rows' => $rows,
            'summaryByQuoteId' => $summaryByQuoteId,
            'totalComparisonByQuoteId' => $totalComparisonByQuoteId,
            'bestVsSecondTotalPercent' => $bestVsSecondTotalPercent,
            'bestPriceQuoteId' => $bestPriceQuoteId,
            'bestLeadQuoteId' => $bestLeadQuoteId,
            'selectedQuoteId' => $selectedQuoteId,
        ];
    }

    private function resolveQuoteComparableTotal(PurchaseQuote $quote): float
    {
        if (! $quote->relationLoaded('items')) {
            return round((float) $quote->total_amount, 2);
        }

        $sumFromLines = round(
            (float) $quote->items
                ->filter(fn (PurchaseQuoteItem $item): bool => $item->unit_price !== null)
                ->sum(function (PurchaseQuoteItem $item): float {
                    if ($item->line_total !== null) {
                        return (float) $item->line_total;
                    }

                    $qty = $item->quoted_qty !== null ? (float) $item->quoted_qty : 0.0;
                    $unitPrice = (float) ($item->unit_price ?? 0);
                    $discountPercent = (float) ($item->discount_percent ?? 0);

                    return round($qty * $unitPrice * (1 - ($discountPercent / 100)), 2);
                }),
            2
        );

        return $sumFromLines > 0 ? $sumFromLines : round((float) $quote->total_amount, 2);
    }

    private function isPriceEquivalent(float $priceA, float $priceB): bool
    {
        return abs($priceA - $priceB) <= self::PRICE_TOLERANCE_EUR;
    }
}


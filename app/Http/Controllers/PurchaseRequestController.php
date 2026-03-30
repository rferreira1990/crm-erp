<?php

namespace App\Http\Controllers;

use App\Http\Requests\Purchases\StorePurchaseRequestRequest;
use App\Http\Requests\Purchases\UpdatePurchaseRequestRequest;
use App\Models\Item;
use App\Models\PurchaseQuote;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Work;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => trim((string) $request->input('status', '')),
            'deadline_from' => trim((string) $request->input('deadline_from', '')),
            'deadline_to' => trim((string) $request->input('deadline_to', '')),
        ];

        $purchaseRequests = PurchaseRequest::query()
            ->with(['work:id,code,name'])
            ->withCount(['items', 'quotes'])
            ->search($filters['search'])
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['deadline_from'] !== '', function ($query) use ($filters) {
                $query->whereDate('deadline_at', '>=', $filters['deadline_from']);
            })
            ->when($filters['deadline_to'] !== '', function ($query) use ($filters) {
                $query->whereDate('deadline_at', '<=', $filters['deadline_to']);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchases.requests.index', [
            'purchaseRequests' => $purchaseRequests,
            'filters' => $filters,
            'statuses' => PurchaseRequest::statuses(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseRequest::class);

        return view('purchases.requests.create', [
            'purchaseRequest' => new PurchaseRequest([
                'status' => PurchaseRequest::STATUS_DRAFT,
            ]),
            'works' => $this->availableWorks(),
            'itemsCatalog' => $this->availableItems(),
            'statuses' => PurchaseRequest::statuses(),
        ]);
    }

    public function store(StorePurchaseRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseRequest::class);

        $validated = $request->validated();
        $resolvedTitle = $this->resolveTitle($validated);

        $purchaseRequest = DB::transaction(function () use ($validated, $resolvedTitle) {
            $purchaseRequest = PurchaseRequest::query()->create([
                'owner_id' => Auth::id(),
                'title' => $resolvedTitle,
                'work_id' => $validated['work_id'] ?? null,
                'needed_at' => $validated['needed_at'] ?? null,
                'deadline_at' => $validated['deadline_at'] ?? null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->syncItems($purchaseRequest, $validated['items']);

            return $purchaseRequest;
        });

        $this->activityLogService->log(
            action: ActivityActions::CREATED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'title' => $purchaseRequest->title,
                'status' => $purchaseRequest->status,
                'work_id' => $purchaseRequest->work_id,
                'items_count' => $purchaseRequest->items()->count(),
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Pedido de cotacao criado com sucesso.');
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest->load([
            'work:id,code,name,status',
            'items.item:id,code,name,unit_id',
            'items.item.unit:id,name',
            'quotes.supplier:id,name,code,email',
            'quotes.creator:id,name',
            'creator:id,name',
            'updater:id,name',
        ]);

        $comparisonQuotes = $purchaseRequest->quotes
            ->sortBy(function (PurchaseQuote $quote) {
                $leadTime = $quote->lead_time_days ?? 999999;

                return [(float) $quote->total_amount, (int) $leadTime, $quote->id];
            })
            ->values();

        $bestPriceQuoteId = $comparisonQuotes->first()?->id;
        $bestLeadQuoteId = $comparisonQuotes
            ->sortBy(fn (PurchaseQuote $quote) => [$quote->lead_time_days ?? 999999, (float) $quote->total_amount, $quote->id])
            ->first()?->id;
        $selectedQuoteId = $comparisonQuotes
            ->firstWhere('status', PurchaseQuote::STATUS_SELECTED)?->id;

        return view('purchases.requests.show', [
            'purchaseRequest' => $purchaseRequest,
            'comparisonQuotes' => $comparisonQuotes,
            'bestPriceQuoteId' => $bestPriceQuoteId,
            'bestLeadQuoteId' => $bestLeadQuoteId,
            'selectedQuoteId' => $selectedQuoteId,
            'statuses' => PurchaseRequest::statuses(),
            'quoteStatuses' => PurchaseQuote::statuses(),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function edit(PurchaseRequest $purchaseRequest): View|RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Apenas pedidos em rascunho ou enviados podem ser editados.');
        }

        $purchaseRequest->load(['items']);

        return view('purchases.requests.edit', [
            'purchaseRequest' => $purchaseRequest,
            'works' => $this->availableWorks(),
            'itemsCatalog' => $this->availableItems(),
            'statuses' => PurchaseRequest::statuses(),
        ]);
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        if (! $purchaseRequest->isEditable()) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Apenas pedidos em rascunho ou enviados podem ser editados.');
        }

        $validated = $request->validated();
        $oldData = $purchaseRequest->only([
            'title',
            'work_id',
            'needed_at',
            'deadline_at',
            'status',
            'notes',
        ]);

        if (
            $validated['status'] !== $purchaseRequest->status
            && ! $purchaseRequest->canChangeTo($validated['status'])
        ) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Transicao de estado invalida para este pedido.');
        }

        DB::transaction(function () use ($purchaseRequest, $validated) {
            $purchaseRequest->update([
                'title' => $this->resolveTitle($validated, $purchaseRequest),
                'work_id' => $validated['work_id'] ?? null,
                'needed_at' => $validated['needed_at'] ?? null,
                'deadline_at' => $validated['deadline_at'] ?? null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $this->syncItems($purchaseRequest, $validated['items']);
        });

        $purchaseRequest->refresh();

        $this->activityLogService->log(
            action: ActivityActions::UPDATED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'old' => $oldData,
                'new' => $purchaseRequest->only(array_keys($oldData)),
                'items_count' => $purchaseRequest->items()->count(),
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Pedido de cotacao atualizado com sucesso.');
    }

    public function changeStatus(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('update', $purchaseRequest);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(PurchaseRequest::statuses()))],
        ]);

        $newStatus = (string) $validated['status'];

        if (! $purchaseRequest->canChangeTo($newStatus)) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Transicao de estado invalida para este pedido.');
        }

        $oldStatus = $purchaseRequest->status;
        $purchaseRequest->update([
            'status' => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            action: ActivityActions::STATUS_CHANGED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: [
                'code' => $purchaseRequest->code,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Estado do pedido atualizado com sucesso.');
    }

    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('delete', $purchaseRequest);

        if (! in_array($purchaseRequest->status, [PurchaseRequest::STATUS_DRAFT, PurchaseRequest::STATUS_CANCELLED], true)) {
            return redirect()
                ->route('purchase-requests.show', $purchaseRequest)
                ->with('error', 'Apenas pedidos em rascunho ou cancelados podem ser removidos.');
        }

        $payload = [
            'code' => $purchaseRequest->code,
            'title' => $purchaseRequest->title,
            'status' => $purchaseRequest->status,
            'quotes_count' => $purchaseRequest->quotes()->count(),
        ];

        $purchaseRequest->delete();

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'purchase_request',
            entityId: $purchaseRequest->id,
            payload: $payload,
            ownerId: $purchaseRequest->owner_id,
            userId: Auth::id(),
        );

        return redirect()
            ->route('purchase-requests.index')
            ->with('success', 'Pedido de cotacao removido com sucesso.');
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function syncItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        $purchaseRequest->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $purchaseRequest->items()->create([
                'item_id' => $item['item_id'] ?? null,
                'description' => $item['description'],
                'qty' => $item['qty'],
                'unit_snapshot' => $item['unit_snapshot'] ?? null,
                'notes' => $item['notes'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function availableWorks()
    {
        return Work::query()
            ->whereIn('status', [
                Work::STATUS_PLANNED,
                Work::STATUS_IN_PROGRESS,
                Work::STATUS_SUSPENDED,
            ])
            ->orderByDesc('id')
            ->get(['id', 'code', 'name', 'status']);
    }

    private function availableItems()
    {
        return Item::query()
            ->with('unit:id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'unit_id']);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function resolveTitle(array $validated, ?PurchaseRequest $current = null): string
    {
        $rawTitle = trim((string) ($validated['title'] ?? ''));
        if ($rawTitle !== '') {
            return Str::limit($rawTitle, 255, '');
        }

        if (! empty($validated['work_id'])) {
            $work = Work::query()
                ->select(['id', 'code'])
                ->find((int) $validated['work_id']);

            if ($work) {
                return 'RFQ ' . $work->code;
            }
        }

        if ($current && $current->title !== '') {
            return $current->title;
        }

        return 'Pedido de cotacao';
    }
}

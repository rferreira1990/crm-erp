<?php

namespace App\Http\Controllers;

use App\Http\Requests\Items\ConfirmItemsImportRequest;
use App\Http\Requests\Items\ImportItemsUploadRequest;
use App\Services\ActivityLogService;
use App\Services\Items\ItemCsvImportService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ItemImportController extends Controller
{
    public function __construct(
        protected ItemCsvImportService $itemCsvImportService,
        protected ActivityLogService $activityLogService
    ) {
    }

    public function show(): View
    {
        $this->authorizeImport();

        return view('items.import', [
            'preview' => null,
            'confirmToken' => null,
            'sourceFileName' => null,
        ]);
    }

    public function templateCsv(): StreamedResponse
    {
        $this->authorizeImport();

        $filename = 'items-import-template.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'code',
                'name',
                'type',
                'item_family',
                'brand',
                'unit',
                'tax_rate',
                'purchase_price',
                'sale_price',
                'tracks_stock',
                'min_stock',
                'max_stock',
                'is_active',
                'notes',
                'barcode',
                'supplier_reference',
                'short_name',
                'max_discount_percent',
            ], ';');

            fputcsv($handle, [
                'ART-000001',
                'Exemplo artigo',
                'product',
                'Tomadas > IP40 > Brancas',
                'Marca Exemplo',
                'UN',
                'Taxa normal',
                '12.50',
                '20.00',
                '1',
                '2',
                '10',
                '1',
                'Descricao opcional',
                '5601234567890',
                'REF-001',
                'Exemplo',
                '0',
            ], ';');

            fclose($handle);
        }, $filename, $headers);
    }

    public function preview(ImportItemsUploadRequest $request): View
    {
        $this->authorizeImport();

        $importFile = $request->file('import_file');
        $result = $this->itemCsvImportService->parseAndValidate($importFile);

        $confirmToken = null;
        if (count($result['errors']) === 0 && count($result['rows']) > 0) {
            $confirmToken = (string) Str::uuid();

            $this->storePreviewPayload($confirmToken, [
                'rows' => $result['rows'],
                'summary' => $result['summary'],
                'source_file_name' => $importFile?->getClientOriginalName(),
            ]);
        }

        return view('items.import', [
            'preview' => $result,
            'confirmToken' => $confirmToken,
            'sourceFileName' => $importFile?->getClientOriginalName(),
        ]);
    }

    public function confirm(ConfirmItemsImportRequest $request): RedirectResponse
    {
        $this->authorizeImport();

        $token = (string) $request->validated('import_token');
        $payload = $this->loadPreviewPayload($token);

        if (! is_array($payload) || empty($payload['rows'])) {
            return redirect()
                ->route('items.import.form')
                ->with('error', 'A pre-visualizacao expirou. Carrega o ficheiro novamente.');
        }

        try {
            $result = $this->itemCsvImportService->runImport($payload['rows']);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('items.import.form')
                ->with('error', 'Ocorreu um erro ao importar os artigos. Nenhuma alteracao foi gravada.');
        }

        $this->deletePreviewPayload($token);

        $this->activityLogService->log(
            action: ActivityActions::IMPORTED,
            entity: 'items_import',
            payload: [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'families_created' => $result['families_created'],
                'brands_created' => $result['brands_created'],
                'source_file_name' => $payload['source_file_name'] ?? null,
            ],
            ownerId: auth()->id(),
            userId: auth()->id(),
        );

        return redirect()
            ->route('items.index')
            ->with('success', 'Importacao concluida: ' . $result['created'] . ' criados e ' . $result['updated'] . ' atualizados.')
            ->with('items_import_summary', $result);
    }

    private function previewStoragePath(string $token): string
    {
        return 'imports/items/' . $token . '.json';
    }

    private function storePreviewPayload(string $token, array $payload): void
    {
        Storage::disk('local')->put(
            $this->previewStoragePath($token),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function loadPreviewPayload(string $token): ?array
    {
        $path = $this->previewStoragePath($token);

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $raw = Storage::disk('local')->get($path);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function deletePreviewPayload(string $token): void
    {
        Storage::disk('local')->delete($this->previewStoragePath($token));
    }

    private function authorizeImport(): void
    {
        abort_unless(
            auth()->user()?->can('items.create')
                && auth()->user()?->can('items.edit'),
            403
        );
    }
}

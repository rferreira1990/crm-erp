<?php

namespace App\Services\Items;

use App\Models\Brand;
use App\Models\Item;
use App\Models\ItemFamily;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemCsvImportService
{
    /**
     * @var array<string, string[]>
     */
    private const HEADER_ALIASES = [
        'code' => ['code', 'codigo'],
        'name' => ['name', 'nome'],
        'type' => ['type', 'tipo'],
        'item_family' => ['item_family', 'family', 'familia', 'family_path', 'familia_path'],
        'brand' => ['brand', 'marca'],
        'unit' => ['unit', 'unidade'],
        'tax_rate' => ['tax_rate', 'iva', 'taxa_iva', 'tax'],
        'purchase_price' => ['purchase_price', 'cost_price', 'preco_custo'],
        'sale_price' => ['sale_price', 'preco_venda'],
        'tracks_stock' => ['tracks_stock', 'controla_stock'],
        'min_stock' => ['min_stock', 'stock_min', 'stock_minimo'],
        'max_stock' => ['max_stock', 'stock_max', 'stock_maximo'],
        'is_active' => ['is_active', 'active', 'ativo'],
        'notes' => ['notes', 'description', 'descricao'],
        'barcode' => ['barcode', 'codigo_barras'],
        'supplier_reference' => ['supplier_reference', 'ref_fornecedor'],
        'short_name' => ['short_name', 'nome_curto'],
        'max_discount_percent' => ['max_discount_percent', 'desconto_max'],
    ];

    /**
     * @param UploadedFile $file
     * @return array<string, mixed>
     */
    public function parseAndValidate(UploadedFile $file): array
    {
        $rawRows = $this->readCsvRows($file);
        if (count($rawRows) === 0) {
            return [
                'summary' => [
                    'total_rows' => 0,
                    'to_create' => 0,
                    'to_update' => 0,
                    'families_to_create' => 0,
                    'brands_to_create' => 0,
                    'errors' => 1,
                ],
                'errors' => [
                    [
                        'line' => 1,
                        'messages' => ['O ficheiro CSV esta vazio.'],
                    ],
                ],
                'rows' => [],
            ];
        }

        $headerRow = array_shift($rawRows);
        $headerMap = $this->resolveHeaderMap($headerRow ?? []);

        $missingColumns = collect(['code', 'name', 'unit', 'tax_rate'])
            ->reject(fn (string $column) => array_key_exists($column, $headerMap))
            ->values()
            ->all();

        if (! empty($missingColumns)) {
            return [
                'summary' => [
                    'total_rows' => 0,
                    'to_create' => 0,
                    'to_update' => 0,
                    'families_to_create' => 0,
                    'brands_to_create' => 0,
                    'errors' => count($missingColumns),
                ],
                'errors' => [
                    [
                        'line' => 1,
                        'messages' => [
                            'Colunas obrigatorias em falta: ' . implode(', ', $missingColumns) . '.',
                        ],
                    ],
                ],
                'rows' => [],
            ];
        }

        $unitLookup = $this->buildUnitLookup();
        $taxRateLookup = $this->buildTaxRateLookup();
        $existingItems = $this->buildExistingItemsLookup();
        $existingBarcodes = $this->buildExistingBarcodesLookup();
        $familyTree = $this->buildFamilyTreeIndex();
        $existingBrands = $this->buildNameLookup(Brand::query()->get(['id', 'name'])->all());

        $pendingBrands = [];
        $seenCodes = [];
        $seenBarcodes = [];

        $normalizedRows = [];
        $errors = [];
        $totalRows = 0;
        $toCreate = 0;
        $toUpdate = 0;
        $familiesToCreate = 0;

        foreach ($rawRows as $rowIndex => $rawRow) {
            if ($this->isBlankRow($rawRow)) {
                continue;
            }

            $totalRows++;
            $line = $rowIndex + 2;
            $row = $this->extractCanonicalRow($rawRow, $headerMap);
            $lineErrors = [];

            $code = $this->cleanText($row['code'] ?? null);
            if ($code === null) {
                $lineErrors[] = 'code obrigatorio.';
            } elseif (mb_strlen($code) > 50) {
                $lineErrors[] = 'code excede 50 caracteres.';
            }

            $codeKey = $code !== null ? $this->normalizeCodeKey($code) : null;
            if ($codeKey !== null) {
                if (isset($seenCodes[$codeKey])) {
                    $lineErrors[] = 'code duplicado no ficheiro (linha ' . $seenCodes[$codeKey] . ').';
                } else {
                    $seenCodes[$codeKey] = $line;
                }
            }

            $existingItem = $codeKey !== null
                ? ($existingItems[$codeKey] ?? null)
                : null;

            $name = $this->cleanText($row['name'] ?? null);
            if ($name === null) {
                $lineErrors[] = 'name obrigatorio.';
            } elseif (mb_strlen($name) > 255) {
                $lineErrors[] = 'name excede 255 caracteres.';
            }

            $type = $this->normalizeType($row['type'] ?? null);
            if ($type === null) {
                $lineErrors[] = 'type invalido. Usa product ou service.';
            }

            $unitValue = $this->cleanText($row['unit'] ?? null);
            $unitId = null;
            if ($unitValue === null) {
                $lineErrors[] = 'unit obrigatoria.';
            } else {
                $resolved = $this->resolveLookupId($unitLookup, $unitValue);
                if ($resolved['status'] === 'not_found') {
                    $lineErrors[] = 'unit nao encontrada: ' . $unitValue . '.';
                } elseif ($resolved['status'] === 'ambiguous') {
                    $lineErrors[] = 'unit ambigua: ' . $unitValue . '. Usa o codigo exato da unidade.';
                } else {
                    $unitId = $resolved['id'];
                }
            }

            $taxRateValue = $this->cleanText($row['tax_rate'] ?? null);
            $taxRateId = null;
            if ($taxRateValue === null) {
                $lineErrors[] = 'tax_rate obrigatoria.';
            } else {
                $resolved = $this->resolveTaxRateId($taxRateLookup, $taxRateValue);
                if ($resolved['status'] === 'not_found') {
                    $lineErrors[] = 'tax_rate nao encontrada: ' . $taxRateValue . '.';
                } elseif ($resolved['status'] === 'ambiguous') {
                    $lineErrors[] = 'tax_rate ambigua: ' . $taxRateValue . '. Usa o nome exato da taxa.';
                } else {
                    $taxRateId = $resolved['id'];
                }
            }

            $purchasePrice = $this->parseDecimal($row['purchase_price'] ?? null, 'purchase_price', $lineErrors, false);
            $salePrice = $this->parseDecimal($row['sale_price'] ?? null, 'sale_price', $lineErrors, false);
            $minStock = $this->parseDecimal($row['min_stock'] ?? null, 'min_stock', $lineErrors, false, 3);
            $maxStock = $this->parseDecimal($row['max_stock'] ?? null, 'max_stock', $lineErrors, false, 3);
            $maxDiscountPercent = $this->parseDecimal($row['max_discount_percent'] ?? null, 'max_discount_percent', $lineErrors, false);

            if ($purchasePrice !== null && $purchasePrice < 0) {
                $lineErrors[] = 'purchase_price deve ser >= 0.';
            }

            if ($salePrice !== null && $salePrice < 0) {
                $lineErrors[] = 'sale_price deve ser >= 0.';
            }

            if ($minStock !== null && $minStock < 0) {
                $lineErrors[] = 'min_stock deve ser >= 0.';
            }

            if ($maxStock !== null && $maxStock < 0) {
                $lineErrors[] = 'max_stock deve ser >= 0.';
            }

            if ($minStock !== null && $maxStock !== null && $maxStock < $minStock) {
                $lineErrors[] = 'max_stock tem de ser >= min_stock.';
            }

            if ($maxDiscountPercent !== null && ($maxDiscountPercent < 0 || $maxDiscountPercent > 100)) {
                $lineErrors[] = 'max_discount_percent deve estar entre 0 e 100.';
            }

            $tracksStockDefault = $type === 'service' ? false : true;
            $tracksStock = $this->parseBoolean($row['tracks_stock'] ?? null, $tracksStockDefault, 'tracks_stock', $lineErrors);
            $isActive = $this->parseBoolean($row['is_active'] ?? null, true, 'is_active', $lineErrors);

            $barcode = $this->cleanText($row['barcode'] ?? null);
            if ($barcode !== null && mb_strlen($barcode) > 100) {
                $lineErrors[] = 'barcode excede 100 caracteres.';
            }

            if ($barcode !== null) {
                $barcodeKey = $this->normalizeLookupKey($barcode);
                $barcodeOwnerItemId = $existingBarcodes[$barcodeKey] ?? null;
                $currentItemId = $existingItem['id'] ?? null;

                if ($barcodeOwnerItemId !== null && $barcodeOwnerItemId !== $currentItemId) {
                    $lineErrors[] = 'barcode ja existe noutro artigo.';
                }

                if (isset($seenBarcodes[$barcodeKey]) && $seenBarcodes[$barcodeKey] !== $codeKey) {
                    $lineErrors[] = 'barcode duplicado no ficheiro.';
                } else {
                    $seenBarcodes[$barcodeKey] = $codeKey;
                }
            }

            $shortName = $this->cleanText($row['short_name'] ?? null);
            if ($shortName !== null && mb_strlen($shortName) > 120) {
                $lineErrors[] = 'short_name excede 120 caracteres.';
            }

            $supplierReference = $this->cleanText($row['supplier_reference'] ?? null);
            if ($supplierReference !== null && mb_strlen($supplierReference) > 100) {
                $lineErrors[] = 'supplier_reference excede 100 caracteres.';
            }

            $notes = $this->cleanText($row['notes'] ?? null, true);
            $familyPath = $this->parseFamilyPath($row['item_family'] ?? null, $lineErrors);
            $brandName = $this->cleanName($row['brand'] ?? null);

            if (! empty($lineErrors)) {
                $errors[] = [
                    'line' => $line,
                    'messages' => $lineErrors,
                ];
                continue;
            }

            $mode = $existingItem !== null ? 'update' : 'create';
            if ($mode === 'create') {
                $toCreate++;
            } else {
                $toUpdate++;
            }

            if ($familyPath !== null) {
                $familiesToCreate += $this->countMissingFamilyNodesForPath($familyPath, $familyTree);
            }

            if ($brandName !== null) {
                $brandKey = $this->normalizeLookupKey($brandName);
                if (! isset($existingBrands[$brandKey]) && ! isset($pendingBrands[$brandKey])) {
                    $pendingBrands[$brandKey] = $brandName;
                }
            }

            $tracksStock = $tracksStock ?? $tracksStockDefault;
            $isActive = $isActive ?? true;

            $normalizedData = [
                'name' => $name,
                'short_name' => $shortName,
                'type' => $type,
                'unit_id' => $unitId,
                'tax_rate_id' => $taxRateId,
                'barcode' => $barcode,
                'supplier_reference' => $supplierReference,
                'cost_price' => $purchasePrice ?? 0,
                'sale_price' => $salePrice ?? 0,
                'tracks_stock' => $tracksStock,
                'min_stock' => $minStock ?? 0,
                'max_stock' => $maxStock,
                'stock_alert' => false,
                'max_discount_percent' => $maxDiscountPercent,
                'is_active' => $isActive,
                'description' => $notes,
            ];

            if ($type === 'service' || ! $tracksStock) {
                $normalizedData['tracks_stock'] = false;
                $normalizedData['min_stock'] = 0;
                $normalizedData['max_stock'] = null;
                $normalizedData['stock_alert'] = false;
            }

            $normalizedRows[] = [
                'line' => $line,
                'mode' => $mode,
                'item_id' => $existingItem['id'] ?? null,
                'code' => $code,
                'family_path' => $familyPath,
                'brand_name' => $brandName,
                'data' => $normalizedData,
            ];
        }

        return [
            'summary' => [
                'total_rows' => $totalRows,
                'to_create' => $toCreate,
                'to_update' => $toUpdate,
                'families_to_create' => $familiesToCreate,
                'brands_to_create' => count($pendingBrands),
                'errors' => count($errors),
            ],
            'errors' => $errors,
            'rows' => count($errors) > 0 ? [] : $normalizedRows,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    public function runImport(array $rows): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'families_created' => 0,
            'brands_created' => 0,
        ];

        DB::transaction(function () use ($rows, &$result): void {
            $familyTree = $this->buildFamilyTreeIndex();
            $brandLookup = $this->buildNameLookup(Brand::query()->get(['id', 'name'])->all());

            foreach ($rows as $row) {
                $familyId = $this->resolveOrCreateFamilyId(
                    $row['family_path'] ?? ($row['family_name'] ?? null),
                    $familyTree,
                    $result
                );

                $brandId = $this->resolveOrCreateBrandId(
                    $row['brand_name'] ?? null,
                    $brandLookup,
                    $result
                );

                $payload = $row['data'];
                $payload['family_id'] = $familyId;
                $payload['brand_id'] = $brandId;

                if (($row['mode'] ?? null) === 'update' && ! empty($row['item_id'])) {
                    $item = Item::withTrashed()
                        ->lockForUpdate()
                        ->findOrFail((int) $row['item_id']);

                    $previousStock = (float) $item->current_stock;

                    if ($item->trashed()) {
                        $item->restore();
                    }

                    $item->update($payload);

                    if ((float) $item->current_stock !== $previousStock) {
                        $item->forceFill([
                            'current_stock' => $previousStock,
                        ])->saveQuietly();
                    }

                    $result['updated']++;
                    continue;
                }

                $payload['code'] = (string) $row['code'];
                $payload['current_stock'] = 0;

                $item = Item::create($payload);

                if ($item->code !== (string) $row['code']) {
                    $item->forceFill([
                        'code' => (string) $row['code'],
                    ])->saveQuietly();
                }

                $result['created']++;
            }
        });

        return $result;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readCsvRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return [];
        }

        $sample = (string) file_get_contents($path, false, null, 0, 4096);
        $delimiter = $this->detectDelimiter($sample);

        $rows = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function detectDelimiter(string $sample): string
    {
        $firstLine = strtok($sample, "\r\n") ?: $sample;

        $candidates = [
            ';' => substr_count($firstLine, ';'),
            ',' => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];

        arsort($candidates);
        $delimiter = array_key_first($candidates);

        return $delimiter ?: ';';
    }

    /**
     * @param array<int, string|null> $headerRow
     * @return array<string, int>
     */
    private function resolveHeaderMap(array $headerRow): array
    {
        $aliasMap = [];
        foreach (self::HEADER_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $aliasMap[$this->normalizeHeaderName($alias)] = $canonical;
            }
        }

        $headerMap = [];
        foreach ($headerRow as $index => $rawHeader) {
            $normalized = $this->normalizeHeaderName((string) $rawHeader);
            if ($normalized === '') {
                continue;
            }

            $canonical = $aliasMap[$normalized] ?? null;
            if ($canonical === null || isset($headerMap[$canonical])) {
                continue;
            }

            $headerMap[$canonical] = $index;
        }

        return $headerMap;
    }

    /**
     * @param array<int, string|null> $row
     * @param array<string, int> $headerMap
     * @return array<string, string|null>
     */
    private function extractCanonicalRow(array $row, array $headerMap): array
    {
        $canonical = [];

        foreach (array_keys(self::HEADER_ALIASES) as $column) {
            $index = $headerMap[$column] ?? null;
            $canonical[$column] = $index === null
                ? null
                : ($row[$index] ?? null);
        }

        return $canonical;
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function buildUnitLookup(): array
    {
        $lookup = [];

        Unit::query()
            ->where('is_active', true)
            ->get(['id', 'code', 'name'])
            ->each(function (Unit $unit) use (&$lookup): void {
                $this->addLookupCandidate($lookup, $unit->code, (int) $unit->id);
                $this->addLookupCandidate($lookup, $unit->name, (int) $unit->id);
                $this->addLookupCandidate($lookup, $unit->code . ' - ' . $unit->name, (int) $unit->id);
            });

        return $lookup;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTaxRateLookup(): array
    {
        $lookup = [];
        $percentLookup = [];

        TaxRate::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'saft_code', 'percent'])
            ->each(function (TaxRate $taxRate) use (&$lookup, &$percentLookup): void {
                $id = (int) $taxRate->id;

                $this->addLookupCandidate($lookup, $taxRate->name, $id);
                $this->addLookupCandidate($lookup, $taxRate->saft_code, $id);
                $this->addLookupCandidate($lookup, $taxRate->saft_code . ' ' . number_format((float) $taxRate->percent, 2, '.', ''), $id);

                $percentKey = number_format((float) $taxRate->percent, 2, '.', '');
                $this->addLookupCandidate($percentLookup, $percentKey, $id);

                if (str_ends_with($percentKey, '.00')) {
                    $this->addLookupCandidate($percentLookup, (string) ((int) $taxRate->percent), $id);
                }
            });

        return [
            'lookup' => $lookup,
            'percent_lookup' => $percentLookup,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildExistingItemsLookup(): array
    {
        $lookup = [];

        Item::withTrashed()
            ->get(['id', 'code', 'barcode', 'deleted_at'])
            ->each(function (Item $item) use (&$lookup): void {
                $codeKey = $this->normalizeCodeKey((string) $item->code);

                $lookup[$codeKey] = [
                    'id' => (int) $item->id,
                    'code' => $item->code,
                    'deleted_at' => $item->deleted_at,
                ];
            });

        return $lookup;
    }

    /**
     * @return array<string, int>
     */
    private function buildExistingBarcodesLookup(): array
    {
        $lookup = [];

        Item::withTrashed()
            ->whereNotNull('barcode')
            ->get(['id', 'barcode'])
            ->each(function (Item $item) use (&$lookup): void {
                $barcode = $this->cleanText($item->barcode);
                if ($barcode === null) {
                    return;
                }

                $lookup[$this->normalizeLookupKey($barcode)] = (int) $item->id;
            });

        return $lookup;
    }

    /**
     * @param array<int, object> $records
     * @return array<string, int>
     */
    private function buildNameLookup(array $records): array
    {
        $lookup = [];

        foreach ($records as $record) {
            $name = $this->cleanName($record->name ?? null);
            if ($name === null) {
                continue;
            }

            $lookup[$this->normalizeLookupKey($name)] = (int) $record->id;
        }

        return $lookup;
    }

    /**
     * @param array<string, array<int, int>> $lookup
     */
    private function addLookupCandidate(array &$lookup, ?string $value, int $id): void
    {
        $value = $this->cleanText($value);
        if ($value === null) {
            return;
        }

        $key = $this->normalizeLookupKey($value);
        if (! isset($lookup[$key])) {
            $lookup[$key] = [];
        }

        $lookup[$key][$id] = $id;
    }

    /**
     * @param array<string, array<int, int>> $lookup
     * @return array{status:string,id:?int}
     */
    private function resolveLookupId(array $lookup, string $value): array
    {
        $key = $this->normalizeLookupKey($value);
        if (! isset($lookup[$key])) {
            return ['status' => 'not_found', 'id' => null];
        }

        $ids = array_values($lookup[$key]);
        if (count($ids) !== 1) {
            return ['status' => 'ambiguous', 'id' => null];
        }

        return ['status' => 'ok', 'id' => (int) $ids[0]];
    }

    /**
     * @param array<string, mixed> $taxRateLookup
     * @return array{status:string,id:?int}
     */
    private function resolveTaxRateId(array $taxRateLookup, string $value): array
    {
        $lookup = $taxRateLookup['lookup'] ?? [];
        $resolved = $this->resolveLookupId($lookup, $value);

        if ($resolved['status'] === 'ok' || $resolved['status'] === 'ambiguous') {
            return $resolved;
        }

        $lineErrors = [];
        $percent = $this->parseDecimal($value, 'tax_rate', $lineErrors, false);
        if ($percent === null) {
            return ['status' => 'not_found', 'id' => null];
        }

        $key = number_format($percent, 2, '.', '');
        $percentLookup = $taxRateLookup['percent_lookup'] ?? [];
        if (! isset($percentLookup[$key])) {
            return ['status' => 'not_found', 'id' => null];
        }

        $ids = array_values($percentLookup[$key]);
        if (count($ids) !== 1) {
            return ['status' => 'ambiguous', 'id' => null];
        }

        return ['status' => 'ok', 'id' => (int) $ids[0]];
    }

    private function normalizeHeaderName(string $value): string
    {
        $value = str_replace("\xEF\xBB\xBF", '', $value);
        $value = Str::of($value)
            ->ascii()
            ->lower()
            ->replace(['-', ' '], '_')
            ->replace('__', '_')
            ->trim('_')
            ->value();

        return trim($value);
    }

    private function normalizeLookupKey(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replace(['-', '/', '\\'], ' ')
            ->squish()
            ->value();
    }

    private function normalizeCodeKey(string $value): string
    {
        return Str::of($value)->trim()->upper()->value();
    }

    private function normalizeType(?string $value): ?string
    {
        $value = $this->cleanText($value);
        if ($value === null) {
            return 'product';
        }

        $normalized = $this->normalizeLookupKey($value);

        return match ($normalized) {
            'product', 'produto' => 'product',
            'service', 'servico' => 'service',
            default => null,
        };
    }

    private function cleanName(?string $value): ?string
    {
        $value = $this->cleanText($value);
        if ($value === null) {
            return null;
        }

        return Str::of($value)->squish()->value();
    }

    /**
     * @param array<int, string> $errors
     */
    private function parseFamilyPath(?string $value, array &$errors): ?string
    {
        $value = $this->cleanText($value);
        if ($value === null) {
            return null;
        }

        $rawSegments = explode('>', $value);
        $segments = [];

        foreach ($rawSegments as $rawSegment) {
            $segment = $this->cleanName($rawSegment);
            if ($segment === null) {
                $errors[] = 'item_family invalida. Usa o formato "Familia > Subfamilia".';
                return null;
            }

            if (mb_strlen($segment) > 120) {
                $errors[] = 'item_family tem segmento com mais de 120 caracteres.';
                return null;
            }

            $segments[] = $segment;
        }

        return implode(' > ', $segments);
    }

    private function cleanFamilyPath(?string $value): ?string
    {
        $value = $this->cleanText($value);
        if ($value === null) {
            return null;
        }

        $segments = [];
        foreach (explode('>', $value) as $rawSegment) {
            $segment = $this->cleanName($rawSegment);
            if ($segment === null) {
                continue;
            }

            $segments[] = $segment;
        }

        if ($segments === []) {
            return null;
        }

        return implode(' > ', $segments);
    }

    /**
     * @return array<int, string>
     */
    private function splitFamilyPath(string $familyPath): array
    {
        $segments = [];

        foreach (explode('>', $familyPath) as $rawSegment) {
            $segment = $this->cleanName($rawSegment);
            if ($segment === null) {
                continue;
            }

            $segments[] = $segment;
        }

        return $segments;
    }

    private function familyParentKey(?int $parentId): string
    {
        return $parentId === null ? 'root' : (string) $parentId;
    }

    private function cleanText(?string $value, bool $allowMultiline = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if ($allowMultiline) {
            return $value;
        }

        return Str::of($value)->squish()->value();
    }

    /**
     * @param array<int, string> $errors
     */
    private function parseDecimal(?string $value, string $field, array &$errors, bool $required = false, int $precision = 2): ?float
    {
        $value = $this->cleanText($value);

        if ($value === null) {
            if ($required) {
                $errors[] = $field . ' obrigatorio.';
            }

            return null;
        }

        $normalized = str_replace(' ', '', $value);
        $normalized = preg_replace('/[^0-9,.\-]/', '', $normalized) ?? '';

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            $errors[] = $field . ' invalido.';
            return null;
        }

        return round((float) $normalized, $precision);
    }

    /**
     * @param array<int, string> $errors
     */
    private function parseBoolean(?string $value, ?bool $default, string $field, array &$errors): ?bool
    {
        $value = $this->cleanText($value);
        if ($value === null) {
            return $default;
        }

        $normalized = $this->normalizeLookupKey($value);

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'sim', 's', 'ativo', 'active'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'nao', 'inativo', 'inactive'], true)) {
            return false;
        }

        $errors[] = $field . ' invalido. Usa 1/0, true/false, sim/nao.';

        return null;
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanText($value) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{children: array<string, array<string, int>>, next_virtual_id: int}
     */
    private function buildFamilyTreeIndex(): array
    {
        $children = [];

        ItemFamily::query()
            ->get(['id', 'parent_id', 'name'])
            ->each(function (ItemFamily $family) use (&$children): void {
                $name = $this->cleanName($family->name);
                if ($name === null) {
                    return;
                }

                $parentKey = $this->familyParentKey($family->parent_id !== null ? (int) $family->parent_id : null);
                $nameKey = $this->normalizeLookupKey($name);

                if (! isset($children[$parentKey])) {
                    $children[$parentKey] = [];
                }

                if (! isset($children[$parentKey][$nameKey])) {
                    $children[$parentKey][$nameKey] = (int) $family->id;
                }
            });

        return [
            'children' => $children,
            'next_virtual_id' => -1,
        ];
    }

    /**
     * @param array{children: array<string, array<string, int>>, next_virtual_id: int} $familyTree
     */
    private function countMissingFamilyNodesForPath(string $familyPath, array &$familyTree): int
    {
        $segments = $this->splitFamilyPath($familyPath);
        if ($segments === []) {
            return 0;
        }

        $created = 0;
        $parentId = null;

        foreach ($segments as $segment) {
            $parentKey = $this->familyParentKey($parentId);
            $nameKey = $this->normalizeLookupKey($segment);

            if (isset($familyTree['children'][$parentKey][$nameKey])) {
                $parentId = (int) $familyTree['children'][$parentKey][$nameKey];
                continue;
            }

            $virtualId = (int) $familyTree['next_virtual_id'];
            $familyTree['next_virtual_id'] = $virtualId - 1;

            if (! isset($familyTree['children'][$parentKey])) {
                $familyTree['children'][$parentKey] = [];
            }

            $familyTree['children'][$parentKey][$nameKey] = $virtualId;
            $parentId = $virtualId;
            $created++;
        }

        return $created;
    }

    /**
     * @param array{children: array<string, array<string, int>>, next_virtual_id: int} $familyTree
     * @param array<string, int> $result
     */
    private function resolveOrCreateFamilyId(?string $familyPath, array &$familyTree, array &$result): ?int
    {
        $familyPath = $this->cleanFamilyPath($familyPath);
        if ($familyPath === null) {
            return null;
        }

        $segments = $this->splitFamilyPath($familyPath);
        if ($segments === []) {
            return null;
        }

        $parentId = null;

        foreach ($segments as $segment) {
            $parentKey = $this->familyParentKey($parentId);
            $nameKey = $this->normalizeLookupKey($segment);

            if (isset($familyTree['children'][$parentKey][$nameKey])) {
                $parentId = (int) $familyTree['children'][$parentKey][$nameKey];
                continue;
            }

            $family = ItemFamily::create([
                'parent_id' => $parentId,
                'name' => $segment,
                'is_active' => true,
            ]);

            if (! isset($familyTree['children'][$parentKey])) {
                $familyTree['children'][$parentKey] = [];
            }

            $familyTree['children'][$parentKey][$nameKey] = (int) $family->id;
            $parentId = (int) $family->id;
            $result['families_created']++;
        }

        return $parentId;
    }

    /**
     * @param array<string, int> $nameLookup
     * @param array<string, int> $result
     */
    private function resolveOrCreateBrandId(?string $brandName, array &$nameLookup, array &$result): ?int
    {
        $brandName = $this->cleanName($brandName);
        if ($brandName === null) {
            return null;
        }

        $key = $this->normalizeLookupKey($brandName);
        if (isset($nameLookup[$key])) {
            return (int) $nameLookup[$key];
        }

        $brand = Brand::create([
            'name' => $brandName,
            'is_active' => true,
        ]);

        $nameLookup[$key] = (int) $brand->id;
        $result['brands_created']++;

        return (int) $brand->id;
    }
}

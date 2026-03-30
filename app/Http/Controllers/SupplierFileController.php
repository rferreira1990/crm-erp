<?php

namespace App\Http\Controllers;

use App\Http\Requests\Suppliers\StoreSupplierFileRequest;
use App\Http\Requests\Suppliers\StoreSupplierLogoRequest;
use App\Models\Supplier;
use App\Models\SupplierFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SupplierFileController extends Controller
{
    public function storeLogo(StoreSupplierLogoRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $uploaded = $request->file('logo');
        if (! $uploaded) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('error', 'Nenhum logo foi enviado.');
        }

        $extension = strtolower($uploaded->getClientOriginalExtension() ?: 'png');
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $path = $uploaded->storeAs('suppliers/' . $supplier->id . '/logo', $fileName, 'public');

        if (! empty($supplier->logo_path)) {
            Storage::disk($supplier->logo_disk ?: 'public')->delete($supplier->logo_path);
        }

        $supplier->update([
            'logo_disk' => 'public',
            'logo_path' => $path,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Logotipo atualizado com sucesso.');
    }

    public function destroyLogo(Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        if (empty($supplier->logo_path)) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('success', 'Este fornecedor nao tem logotipo.');
        }

        Storage::disk($supplier->logo_disk ?: 'public')->delete($supplier->logo_path);

        $supplier->update([
            'logo_path' => null,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Logotipo removido com sucesso.');
    }

    public function store(StoreSupplierFileRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $uploadedFiles = $request->file('files', []);
        if (empty($uploadedFiles)) {
            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('error', 'Nenhum ficheiro foi enviado.');
        }

        $storedPaths = [];

        try {
            DB::transaction(function () use ($uploadedFiles, $supplier, &$storedPaths) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $realMimeType = $this->detectMimeType($uploadedFile->getPathname());
                    $type = $this->resolveFileType($realMimeType);
                    $extension = $this->resolveExtension($realMimeType, $uploadedFile->getClientOriginalExtension());
                    $generatedName = Str::uuid()->toString() . '.' . $extension;

                    $folder = 'suppliers/' . $supplier->id . '/attachments/' . $type;
                    $path = $uploadedFile->storeAs($folder, $generatedName, 'local');

                    $storedPaths[] = $path;

                    SupplierFile::query()->create([
                        'supplier_id' => $supplier->id,
                        'disk' => 'local',
                        'file_path' => $path,
                        'file_name' => $generatedName,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'mime_type' => $realMimeType,
                        'file_size' => $uploadedFile->getSize() ?: 0,
                        'type' => $type,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            report($exception);

            return redirect()
                ->route('suppliers.show', $supplier)
                ->with('error', 'Ocorreu um erro ao carregar anexos.');
        }

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Anexos carregados com sucesso.');
    }

    public function show(Supplier $supplier, SupplierFile $file): StreamedResponse
    {
        $this->authorize('view', $supplier);

        if ((int) $file->supplier_id !== (int) $supplier->id) {
            abort(404);
        }

        if (! Storage::disk($file->disk)->exists($file->file_path)) {
            abort(404);
        }

        return Storage::disk($file->disk)->response(
            $file->file_path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    public function destroy(Supplier $supplier, SupplierFile $file): RedirectResponse
    {
        $this->authorize('update', $supplier);

        if ((int) $file->supplier_id !== (int) $supplier->id) {
            abort(404);
        }

        Storage::disk($file->disk)->delete($file->file_path);
        $file->delete();

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Anexo removido com sucesso.');
    }

    private function detectMimeType(string $path): string
    {
        $mimeType = mime_content_type($path);

        return is_string($mimeType) && $mimeType !== ''
            ? $mimeType
            : 'application/octet-stream';
    }

    private function resolveFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if ($mimeType === 'application/pdf') {
            return 'catalog';
        }

        if (str_contains($mimeType, 'zip') || str_contains($mimeType, 'rar') || str_contains($mimeType, '7z')) {
            return 'archive';
        }

        return 'document';
    }

    private function resolveExtension(string $mimeType, ?string $clientExtension): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/x-7z-compressed' => '7z',
        ];

        if (isset($map[$mimeType])) {
            return $map[$mimeType];
        }

        $normalizedClientExtension = strtolower((string) $clientExtension);

        return $normalizedClientExtension !== '' ? $normalizedClientExtension : 'bin';
    }
}


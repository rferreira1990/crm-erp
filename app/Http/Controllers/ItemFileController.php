<?php

namespace App\Http\Controllers;

use App\Http\Requests\Items\StoreItemFileRequest;
use App\Models\Item;
use App\Models\ItemFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemFileController extends Controller
{
    public function store(StoreItemFileRequest $request, Item $item): RedirectResponse
    {
        $uploadedFiles = $request->file('files', []);

        if (empty($uploadedFiles)) {
            return redirect()
                ->route('items.edit', $item)
                ->with('error', 'Nenhum ficheiro foi enviado.');
        }

        $nextSortOrder = (int) $item->files()->max('sort_order');

        DB::transaction(function () use ($uploadedFiles, $item, &$nextSortOrder) {
            foreach ($uploadedFiles as $uploadedFile) {
                $mimeType = $uploadedFile->getMimeType() ?: $uploadedFile->getClientMimeType();
                $extension = strtolower($uploadedFile->getClientOriginalExtension());
                $type = $mimeType === 'application/pdf' || $extension === 'pdf' ? 'pdf' : 'image';

                $generatedName = Str::uuid()->toString() . '.' . $extension;
                $folder = 'items/' . $item->id . '/' . ($type === 'image' ? 'images' : 'documents');
                $path = $uploadedFile->storeAs($folder, $generatedName, 'public');

                $nextSortOrder++;

                $isPrimary = false;

                if ($type === 'image' && ! $item->images()->where('is_primary', true)->exists()) {
                    $isPrimary = true;
                }

                ItemFile::create([
                    'item_id' => $item->id,
                    'disk' => 'public',
                    'file_path' => $path,
                    'file_name' => $generatedName,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $mimeType ?: 'application/octet-stream',
                    'file_size' => $uploadedFile->getSize() ?: 0,
                    'type' => $type,
                    'is_primary' => $isPrimary,
                    'sort_order' => $nextSortOrder,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Ficheiros carregados com sucesso.');
    }

    public function destroy(Item $item, ItemFile $file): RedirectResponse
    {
        if ($file->item_id !== $item->id) {
            abort(404);
        }

        DB::transaction(function () use ($item, $file) {
            Storage::disk($file->disk)->delete($file->file_path);

            $wasPrimaryImage = $file->type === 'image' && $file->is_primary;

            $file->delete();

            if ($wasPrimaryImage) {
                $newPrimary = $item->images()->orderBy('sort_order')->orderBy('id')->first();

                if ($newPrimary) {
                    $newPrimary->update([
                        'is_primary' => true,
                        'updated_by' => auth()->id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Ficheiro removido com sucesso.');
    }

    public function setPrimary(Item $item, ItemFile $file): RedirectResponse
    {
        if ($file->item_id !== $item->id || $file->type !== 'image') {
            abort(404);
        }

        DB::transaction(function () use ($item, $file) {
            $item->images()->update([
                'is_primary' => false,
                'updated_by' => auth()->id(),
            ]);

            $file->update([
                'is_primary' => true,
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Imagem principal definida com sucesso.');
    }
}

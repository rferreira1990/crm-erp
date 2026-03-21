<?php

namespace App\Http\Controllers;

use App\Http\Requests\Items\StoreItemFileRequest;
use App\Models\Item;
use App\Models\ItemFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

        $storedPaths = [];
        $nextSortOrder = (int) $item->files()->max('sort_order');

        try {
            DB::transaction(function () use ($uploadedFiles, $item, &$nextSortOrder, &$storedPaths) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $realMimeType = $this->detectMimeType($uploadedFile->getPathname());
                    $type = $this->resolveFileType($realMimeType);
                    $extension = $this->resolveExtension($realMimeType);

                    $generatedName = Str::uuid()->toString() . '.' . $extension;
                    $folder = 'items/' . $item->id . '/' . ($type === 'image' ? 'images' : 'documents');
                    $path = $uploadedFile->storeAs($folder, $generatedName, 'local');

                    $storedPaths[] = [
                        'disk' => 'local',
                        'path' => $path,
                    ];

                    $thumbPath = null;
                    $thumbDisk = null;

                    if ($type === 'image') {
                        $thumbName = Str::uuid()->toString() . '_thumb.' . $extension;
                        $thumbFolder = 'items/' . $item->id . '/thumbnails';
                        $thumbPath = $thumbFolder . '/' . $thumbName;
                        $thumbDisk = 'local';

                        $this->generateImageThumbnail(
                            $uploadedFile->getPathname(),
                            Storage::disk($thumbDisk)->path($thumbPath),
                            $realMimeType,
                            400,
                            400
                        );

                        $storedPaths[] = [
                            'disk' => $thumbDisk,
                            'path' => $thumbPath,
                        ];
                    }

                    $nextSortOrder++;

                    $isPrimary = false;

                    if ($type === 'image' && ! $item->images()->where('is_primary', true)->exists()) {
                        $isPrimary = true;
                    }

                    ItemFile::create([
                        'item_id' => $item->id,
                        'disk' => 'local',
                        'thumb_disk' => $thumbDisk,
                        'file_path' => $path,
                        'thumb_path' => $thumbPath,
                        'file_name' => $generatedName,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'mime_type' => $realMimeType,
                        'file_size' => $uploadedFile->getSize() ?: 0,
                        'type' => $type,
                        'is_primary' => $isPrimary,
                        'sort_order' => $nextSortOrder,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            });
        } catch (Throwable $e) {
            foreach ($storedPaths as $storedFile) {
                Storage::disk($storedFile['disk'])->delete($storedFile['path']);
            }

            report($e);

            return redirect()
                ->route('items.edit', $item)
                ->with('error', 'Ocorreu um erro ao carregar os ficheiros.');
        }

        return redirect()
            ->route('items.edit', $item)
            ->with('success', 'Ficheiros carregados com sucesso.');
    }

    public function show(Item $item, ItemFile $file): StreamedResponse
    {
        if ($file->item_id !== $item->id) {
            abort(404);
        }

        $variant = request()->query('variant');
        $disk = $file->disk;
        $path = $file->file_path;
        $downloadName = $file->original_name;
        $contentType = $file->mime_type;

        if (
            $variant === 'thumb' &&
            $file->isImage() &&
            ! empty($file->thumb_disk) &&
            ! empty($file->thumb_path)
        ) {
            $disk = $file->thumb_disk;
            $path = $file->thumb_path;
        }

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            $path,
            $downloadName,
            [
                'Content-Type' => $contentType,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    public function destroy(Item $item, ItemFile $file): RedirectResponse
    {
        if ($file->item_id !== $item->id) {
            abort(404);
        }

        DB::transaction(function () use ($item, $file) {
            Storage::disk($file->disk)->delete($file->file_path);

            if (! empty($file->thumb_disk) && ! empty($file->thumb_path)) {
                Storage::disk($file->thumb_disk)->delete($file->thumb_path);
            }

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

    private function detectMimeType(string $path): string
    {
        $mimeType = mime_content_type($path);

        return is_string($mimeType) && $mimeType !== ''
            ? $mimeType
            : 'application/octet-stream';
    }

    private function resolveFileType(string $mimeType): string
    {
        if ($mimeType === 'application/pdf') {
            return 'pdf';
        }

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        abort(422, 'Tipo de ficheiro não suportado.');
    }

    private function resolveExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => throw new \RuntimeException('Não foi possível determinar a extensão do ficheiro.'),
        };
    }

    private function generateImageThumbnail(
        string $sourcePath,
        string $destinationPath,
        string $mimeType,
        int $maxWidth,
        int $maxHeight
    ): void {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('A extensão GD não está disponível no servidor.');
        }

        $imageInfo = @getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new \RuntimeException('Não foi possível ler a imagem para gerar thumbnail.');
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            throw new \RuntimeException('Dimensões da imagem inválidas.');
        }

        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $thumbWidth = (int) max(1, round($sourceWidth * $ratio));
        $thumbHeight = (int) max(1, round($sourceHeight * $ratio));

        $sourceImage = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($sourceImage === false) {
            throw new \RuntimeException('Não foi possível abrir a imagem original.');
        }

        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);

        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
            $transparent = imagecolorallocatealpha($thumbImage, 0, 0, 0, 127);
            imagefilledrectangle($thumbImage, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }

        imagecopyresampled(
            $thumbImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $thumbWidth,
            $thumbHeight,
            $sourceWidth,
            $sourceHeight
        );

        $directory = dirname($destinationPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            imagedestroy($sourceImage);
            imagedestroy($thumbImage);

            throw new \RuntimeException('Não foi possível criar a pasta da thumbnail.');
        }

        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg($thumbImage, $destinationPath, 82),
            'image/png' => imagepng($thumbImage, $destinationPath, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($thumbImage, $destinationPath, 82) : false,
            default => false,
        };

        imagedestroy($sourceImage);
        imagedestroy($thumbImage);

        if ($saved === false) {
            throw new \RuntimeException('Não foi possível guardar a thumbnail.');
        }
    }
}

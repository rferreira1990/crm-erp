<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\StoreWorkFileRequest;
use App\Models\Work;
use App\Models\WorkDailyReport;
use App\Models\WorkFile;
use App\Services\ActivityLogService;
use App\Support\ActivityActions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class WorkFileController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    public function index(Work $work): View
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('viewAny', [WorkFile::class, $work]);

        $files = WorkFile::query()
            ->where('owner_id', $work->owner_id)
            ->where('work_id', $work->id)
            ->with([
                'user:id,name',
                'dailyReport:id,work_id,report_date,day_status',
            ])
            ->orderByDesc('id')
            ->paginate(20);

        $dailyReportOptions = WorkDailyReport::query()
            ->where('owner_id', $work->owner_id)
            ->where('work_id', $work->id)
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'report_date', 'day_status']);

        return view('works.files.index', [
            'work' => $work,
            'files' => $files,
            'dailyReportOptions' => $dailyReportOptions,
            'fileCategories' => WorkFile::categories(),
            'maxFileSizeKb' => (int) config('work_files.max_kb', 10240),
        ]);
    }

    public function store(StoreWorkFileRequest $request, Work $work): RedirectResponse
    {
        $this->ensureWorkRouteScope($work);
        $this->authorize('create', [WorkFile::class, $work]);

        $validated = $request->validated();
        $uploadedFile = $request->file('file');

        if (! $uploadedFile) {
            return $this->redirectBackToFiles($work)->with('error', 'Nenhum ficheiro foi enviado.');
        }

        $realMimeType = mime_content_type($uploadedFile->getPathname()) ?: 'application/octet-stream';
        $extension = $this->resolveExtension($uploadedFile->getClientOriginalExtension(), $realMimeType);
        $generatedName = Str::uuid()->toString() . '.' . $extension;
        $directory = 'works/' . $work->owner_id . '/' . $work->id . '/files';
        $storedPath = null;

        try {
            DB::transaction(function () use (
                $work,
                $validated,
                $uploadedFile,
                $generatedName,
                $directory,
                $realMimeType,
                &$storedPath
            ): void {
                $storedPath = $uploadedFile->storeAs($directory, $generatedName, 'local');

                $workFile = WorkFile::query()->create([
                    'owner_id' => $work->owner_id,
                    'work_id' => $work->id,
                    'work_daily_report_id' => $validated['work_daily_report_id'] ?? null,
                    'user_id' => Auth::id(),
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'file_name' => $generatedName,
                    'file_path' => $storedPath,
                    'mime_type' => $realMimeType,
                    'file_size' => $uploadedFile->getSize() ?: 0,
                    'category' => $validated['category'],
                ]);

                $this->activityLogService->log(
                    action: ActivityActions::CREATED,
                    entity: 'work_file',
                    entityId: $workFile->id,
                    payload: [
                        'work_id' => $work->id,
                        'work_code' => $work->code,
                        'work_daily_report_id' => $workFile->work_daily_report_id,
                        'original_name' => $workFile->original_name,
                        'mime_type' => $workFile->mime_type,
                        'file_size' => (int) $workFile->file_size,
                        'category' => $workFile->category,
                    ],
                    ownerId: $work->owner_id,
                    userId: Auth::id(),
                );
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            report($exception);

            return $this->redirectBackToFiles($work)
                ->with('error', 'Ocorreu um erro ao carregar o ficheiro.');
        }

        return $this->redirectBackToFiles($work)
            ->with('success', 'Ficheiro carregado com sucesso.');
    }

    public function download(Work $work, WorkFile $file): StreamedResponse
    {
        $this->ensureFileRouteScope($work, $file);
        $this->authorize('view', $file);

        if (! Storage::disk('local')->exists($file->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $file->file_path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    public function destroy(Work $work, WorkFile $file): RedirectResponse
    {
        $this->ensureFileRouteScope($work, $file);
        $this->authorize('delete', $file);

        Storage::disk('local')->delete($file->file_path);

        $this->activityLogService->log(
            action: ActivityActions::DELETED,
            entity: 'work_file',
            entityId: $file->id,
            payload: [
                'work_id' => $work->id,
                'work_code' => $work->code,
                'work_daily_report_id' => $file->work_daily_report_id,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'file_size' => (int) $file->file_size,
                'category' => $file->category,
            ],
            ownerId: $work->owner_id,
            userId: Auth::id(),
        );

        $file->delete();

        return $this->redirectBackToFiles($work)
            ->with('success', 'Ficheiro removido com sucesso.');
    }

    private function ensureWorkRouteScope(Work $work): void
    {
        abort_if((int) $work->owner_id !== (int) Auth::id(), 404);
    }

    private function ensureFileRouteScope(Work $work, WorkFile $file): void
    {
        if ((int) $file->work_id !== (int) $work->id) {
            abort(404);
        }

        if ((int) $file->owner_id !== (int) $work->owner_id) {
            abort(404);
        }

        $this->ensureWorkRouteScope($work);
    }

    private function redirectBackToFiles(Work $work): RedirectResponse
    {
        $target = url()->previous();
        $fallback = route('works.files.index', $work);

        if (! is_string($target) || $target === '') {
            $target = $fallback;
        }

        return redirect()->to($target);
    }

    private function resolveExtension(?string $clientExtension, string $mimeType): string
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
            'text/plain' => 'txt',
            'text/csv' => 'csv',
        ];

        if (isset($map[$mimeType])) {
            return $map[$mimeType];
        }

        $normalized = strtolower((string) $clientExtension);

        return $normalized !== '' ? $normalized : 'bin';
    }
}

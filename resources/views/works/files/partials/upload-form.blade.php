@php
    $fileCategories = \App\Models\WorkFile::categories();
    $maxFileSizeKb = (int) config('work_files.max_kb', 10240);
    $maxFileSizeMb = number_format($maxFileSizeKb / 1024, 0, ',', '.');
@endphp

<form method="POST" action="{{ route('works.files.store', $work) }}" enctype="multipart/form-data">
    @csrf

    <div class="row g-3">
        <div class="col-md-3">
            <label for="work_file_category" class="form-label">Categoria <span class="text-danger">*</span></label>
            <select id="work_file_category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                @foreach ($fileCategories as $categoryValue => $categoryLabel)
                    <option value="{{ $categoryValue }}" @selected(old('category', \App\Models\WorkFile::CATEGORY_DOCUMENT) === $categoryValue)>
                        {{ $categoryLabel }}
                    </option>
                @endforeach
            </select>
            @error('category')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="work_file_daily_report_id" class="form-label">Registo diario (opcional)</label>
            <select
                id="work_file_daily_report_id"
                name="work_daily_report_id"
                class="form-select @error('work_daily_report_id') is-invalid @enderror"
            >
                <option value="">Sem associacao ao diario</option>
                @foreach ($dailyReportOptions as $dailyReportOption)
                    @php
                        $statusLabel = \App\Models\WorkDailyReport::statuses()[$dailyReportOption->day_status] ?? $dailyReportOption->day_status;
                        $optionLabel = ($dailyReportOption->report_date?->format('d/m/Y') ?? '-') . ' - ' . $statusLabel;
                    @endphp
                    <option value="{{ $dailyReportOption->id }}" @selected((string) old('work_daily_report_id') === (string) $dailyReportOption->id)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
            @error('work_daily_report_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-5">
            <label for="work_file_input" class="form-label">Ficheiro <span class="text-danger">*</span></label>
            <input
                id="work_file_input"
                type="file"
                name="file"
                class="form-control @error('file') is-invalid @enderror"
                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv"
                required
            >
            @error('file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text">
                Tipos permitidos: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX, TXT, CSV.
                Tamanho maximo por ficheiro: {{ $maxFileSizeMb }} MB.
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary">Adicionar ficheiro</button>
    </div>
</form>


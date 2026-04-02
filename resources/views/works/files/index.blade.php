@extends('layouts.admin')

@section('title', 'Ficheiros da Obra')

@section('content')
@php
    $canUpdateWork = auth()->user()?->can('works.update');
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Ficheiros da Obra</h2>
        <div class="text-muted">{{ $work->code }} - {{ $work->name }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('works.show', $work) }}" class="btn btn-outline-secondary">Voltar a obra</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($canUpdateWork)
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <strong>Adicionar ficheiro</strong>
        </div>
        <div class="card-body">
            @include('works.files.partials.upload-form', [
                'work' => $work,
                'dailyReportOptions' => $dailyReportOptions,
            ])
        </div>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Lista de ficheiros</strong>
        <span class="badge bg-light text-dark border">{{ $files->total() }}</span>
    </div>
    <div class="card-body">
        @if ($files->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Diario</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Carregado em</th>
                            <th>Utilizador</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($files as $file)
                            <tr>
                                <td>{{ $file->original_name }}</td>
                                <td>{{ \App\Models\WorkFile::categories()[$file->category] ?? $file->category }}</td>
                                <td>
                                    @if ($file->dailyReport)
                                        {{ $file->dailyReport->report_date?->format('d/m/Y') ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $file->mime_type }}</td>
                                <td>{{ $file->readable_size }}</td>
                                <td>{{ $file->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>{{ $file->user?->name ?? '-' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('works.files.download', [$work, $file]) }}" class="btn btn-sm btn-outline-primary">
                                            Download
                                        </a>

                                        @if ($canUpdateWork)
                                            <form method="POST" action="{{ route('works.files.destroy', [$work, $file]) }}" onsubmit="return confirm('Remover este ficheiro?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $files->links() }}
            </div>
        @else
            <div class="text-muted">Sem ficheiros associados a esta obra.</div>
        @endif
    </div>
</div>
@endsection


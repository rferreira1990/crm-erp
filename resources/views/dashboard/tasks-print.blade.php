<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Impressao de tarefas - {{ $selectedDate->format('d/m/Y') }}</title>
    <link rel="stylesheet" href="{{ asset('porto/css/csp-hardening.css') }}">
</head>
<body class="tasks-print-page">
@php
    $formatTime = static function (?string $time): ?string {
        if ($time === null || trim($time) === '') {
            return null;
        }

        return substr($time, 0, 5);
    };
@endphp

<main class="print-sheet">
    <header class="sheet-header">
        <div class="header-top">
            <div>
                <h1>Tarefas planeadas</h1>
                <p class="sheet-subtitle">Folha de trabalho para a data selecionada</p>
            </div>
            <div class="header-actions no-print">
                <a href="{{ route('dashboard') }}" class="btn">Voltar ao dashboard</a>
                <button type="button" class="btn btn-primary js-trigger-print">Imprimir</button>
            </div>
        </div>

        <div class="sheet-meta">
            <div class="meta-box">
                <p class="meta-label">Data das tarefas</p>
                <p class="meta-value">{{ $selectedDate->format('d/m/Y') }}</p>
            </div>
            <div class="meta-box">
                <p class="meta-label">Total de tarefas</p>
                <p class="meta-value">{{ $tasks->count() }}</p>
            </div>
            <div class="meta-box">
                <p class="meta-label">Gerado em</p>
                <p class="meta-value">{{ $generatedAt->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </header>

    <section class="sheet-body">
        @if ($tasks->isEmpty())
            <div class="empty">Nao existem tarefas planeadas para esta data.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th class="th-time">Hora</th>
                        <th class="th-task">Tarefa</th>
                        <th class="th-work">Obra / Cliente</th>
                        <th class="th-responsible">Responsavel / Equipa</th>
                        <th class="th-status">Estado</th>
                        <th class="th-notes">Observacoes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tasks as $task)
                        @php
                            $startTime = $formatTime($task->planned_start_time);
                            $endTime = $formatTime($task->planned_end_time);
                            $timeLabel = '-';

                            if ($startTime && $endTime) {
                                $timeLabel = $startTime . ' - ' . $endTime;
                            } elseif ($startTime) {
                                $timeLabel = $startTime;
                            } elseif ($endTime) {
                                $timeLabel = $endTime;
                            }

                            $responsibleNames = collect();

                            if ($task->assignedUser?->name) {
                                $responsibleNames->push($task->assignedUser->name);
                            }

                            $responsibleNames = $responsibleNames
                                ->merge($task->assignments->pluck('user.name')->filter())
                                ->unique()
                                ->values();

                            $statusLabel = $statusLabels[$task->status] ?? (string) $task->status;
                        @endphp
                        <tr>
                            <td>{{ $timeLabel }}</td>
                            <td>
                                <div class="work-label">{{ $task->title }}</div>
                                <div class="description muted">{{ $task->description ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="work-label">
                                    {{ $task->work?->code ? $task->work->code . ' - ' . $task->work->name : ($task->work?->name ?: '-') }}
                                </div>
                                <div class="customer-label">
                                    {{ $task->work?->customer?->code ? $task->work->customer->code . ' - ' . $task->work->customer->name : ($task->work?->customer?->name ?: '-') }}
                                </div>
                            </td>
                            <td>
                                @if ($responsibleNames->isNotEmpty())
                                    {{ $responsibleNames->join(', ') }}
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="status">{{ $statusLabel }}</span>
                            </td>
                            <td class="description">{{ $task->description ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</main>
<script src="{{ asset('porto/js/pages/csp-interactions.js') }}"></script>
</body>
</html>

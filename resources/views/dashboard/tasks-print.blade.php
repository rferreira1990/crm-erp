<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Impressao de tarefas - {{ $selectedDate->format('d/m/Y') }}</title>
    <style>
        :root {
            --border: #d5dbe3;
            --text: #1f2937;
            --muted: #6b7280;
            --bg-head: #f3f6fa;
            --status-bg: #eef2ff;
            --status-text: #1e40af;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 16px;
            background: #f3f4f6;
            color: var(--text);
            font-family: "Poppins", "Segoe UI", Arial, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }

        .print-sheet {
            max-width: 1120px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .sheet-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            background: #fff;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        h1 {
            margin: 0;
            font-size: 21px;
            line-height: 1.2;
        }

        .sheet-subtitle {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid #c4ccda;
            border-radius: 6px;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-primary {
            border-color: #1d6fa5;
            background: #1d6fa5;
            color: #fff;
        }

        .sheet-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .meta-box {
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px;
            background: #fff;
        }

        .meta-label {
            margin: 0 0 4px;
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .meta-value {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }

        .sheet-body {
            padding: 16px 20px 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid var(--border);
            padding: 8px;
            vertical-align: top;
        }

        th {
            background: var(--bg-head);
            text-align: left;
            font-weight: 600;
            font-size: 12px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--status-bg);
            color: var(--status-text);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .muted {
            color: var(--muted);
        }

        .description {
            white-space: pre-wrap;
        }

        .empty {
            border: 1px dashed var(--border);
            border-radius: 8px;
            padding: 18px;
            text-align: center;
            color: var(--muted);
            background: #fafbfc;
        }

        .work-label {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .customer-label {
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .sheet-meta {
                grid-template-columns: 1fr;
            }
        }

        @page {
            size: A4;
            margin: 12mm;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                font-size: 11px;
            }

            .no-print {
                display: none !important;
            }

            .print-sheet {
                max-width: none;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .sheet-header,
            .sheet-body {
                padding-left: 0;
                padding-right: 0;
            }

            th,
            td {
                padding: 6px;
            }
        }
    </style>
</head>
<body>
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
                        <th style="width: 8%;">Hora</th>
                        <th style="width: 27%;">Tarefa</th>
                        <th style="width: 22%;">Obra / Cliente</th>
                        <th style="width: 18%;">Responsavel / Equipa</th>
                        <th style="width: 10%;">Estado</th>
                        <th style="width: 15%;">Observacoes</th>
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

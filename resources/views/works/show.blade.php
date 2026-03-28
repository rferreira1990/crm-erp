@extends('layouts.admin')

@section('title', 'Detalhe da Obra')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $work->name }}</h1>

                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                    @if($work->status === \App\Models\Work::STATUS_PLANNED) bg-gray-100 text-gray-700
                    @elseif($work->status === \App\Models\Work::STATUS_IN_PROGRESS) bg-blue-100 text-blue-700
                    @elseif($work->status === \App\Models\Work::STATUS_SUSPENDED) bg-yellow-100 text-yellow-700
                    @elseif($work->status === \App\Models\Work::STATUS_COMPLETED) bg-green-100 text-green-700
                    @elseif($work->status === \App\Models\Work::STATUS_CANCELLED) bg-red-100 text-red-700
                    @else bg-gray-100 text-gray-700
                    @endif">
                    {{ $work->status_label }}
                </span>
            </div>

            <p class="text-sm text-gray-500">Código: {{ $work->code }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('works.update')
                <a href="{{ route('works.edit', $work) }}"
                   class="rounded-md border border-blue-300 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">
                    Editar
                </a>
            @endcan

            @can('works.delete')
                <form method="POST" action="{{ route('works.destroy', $work) }}"
                      onsubmit="return confirm('Tens a certeza que queres apagar esta obra?');">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                        Apagar
                    </button>
                </form>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Dados principais</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cliente</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->customer?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de obra</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->work_type ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Responsável técnico</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->technicalManager?->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Orçamento associado</p>
                        <p class="mt-1 text-sm text-gray-900">
                            @if ($work->budget)
                                <a href="{{ route('budgets.show', $work->budget) }}" class="text-blue-600 hover:underline">
                                    {{ $work->budget->code }}
                                </a>
                            @else
                                -
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Morada / Local</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->location ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Código Postal / Cidade</p>
                        <p class="mt-1 text-sm text-gray-900">
                            {{ trim(($work->postal_code ?? '') . ' ' . ($work->city ?? '')) ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Início previsto</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->start_date_planned?->format('d/m/Y') ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fim previsto</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->end_date_planned?->format('d/m/Y') ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Início real</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->start_date_actual?->format('d/m/Y') ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fim real</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $work->end_date_actual?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Descrição</h2>
                </div>

                <div class="p-6">
                    <div class="text-sm leading-6 text-gray-700 whitespace-pre-line">
                        {{ $work->description ?: 'Sem descrição.' }}
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Notas internas</h2>
                </div>

                <div class="p-6">
                    <div class="text-sm leading-6 text-gray-700 whitespace-pre-line">
                        {{ $work->internal_notes ?: 'Sem notas internas.' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Equipa</h2>
                </div>

                <div class="p-6">
                    @if ($work->team->count())
                        <ul class="space-y-2">
                            @foreach ($work->team as $member)
                                <li class="rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                    {{ $member->name }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">Sem elementos associados.</p>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Histórico de estados</h2>
                </div>

                <div class="p-6">
                    @if ($work->statusHistories->count())
                        <div class="space-y-4">
                            @foreach ($work->statusHistories as $history)
                                <div class="border-l-2 border-gray-200 pl-4">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ \App\Models\Work::statuses()[$history->new_status] ?? $history->new_status }}
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        {{ $history->created_at?->format('d/m/Y H:i') ?? '-' }}
                                        @if ($history->changedBy)
                                            · {{ $history->changedBy->name }}
                                        @endif
                                    </p>

                                    @if ($history->notes)
                                        <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">{{ $history->notes }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Ainda não existe histórico de estados.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

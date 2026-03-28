@extends('layouts.admin')

@section('title', 'Obras')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Obras</h1>
            <p class="text-sm text-gray-600">Gestão de obras e trabalhos em curso.</p>
        </div>

        @can('works.create')
            <a href="{{ route('works.create') }}"
               class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
                Nova obra
            </a>
        @endcan
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

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Obra</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Responsável</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Data prevista</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Ações</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($works as $work)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $work->code }}
                            </td>

                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900">{{ $work->name }}</div>
                                @if ($work->work_type)
                                    <div class="text-xs text-gray-500">{{ $work->work_type }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $work->customer?->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm">
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
                            </td>

                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $work->technicalManager?->name ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $work->start_date_planned?->format('d/m/Y') ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    @can('works.view')
                                        <a href="{{ route('works.show', $work) }}"
                                           class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                            Ver
                                        </a>
                                    @endcan

                                    @can('works.update')
                                        <a href="{{ route('works.edit', $work) }}"
                                           class="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50">
                                            Editar
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                Ainda não existem obras registadas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($works->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $works->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

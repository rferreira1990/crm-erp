@extends('layouts.admin')

@section('title', 'Editar Obra')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Editar obra</h1>
            <p class="text-sm text-gray-600">Atualiza os dados da obra {{ $work->code }}.</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="p-6">
            <form method="POST" action="{{ route('works.update', $work) }}">
                @csrf
                @method('PUT')

                @include('works.partials.form', [
                    'work' => $work,
                ])

                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('works.show', $work) }}"
                       class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Voltar
                    </a>

                    <button type="submit"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Atualizar obra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

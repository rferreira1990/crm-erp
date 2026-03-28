@extends('layouts.admin')

@section('title', 'Nova Obra')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Nova obra</h1>
        <p class="text-sm text-gray-600">Cria uma nova obra associada a um cliente.</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="p-6">
            <form method="POST" action="{{ route('works.store') }}">
                @csrf

                @include('works.partials.form', [
                    'work' => null,
                ])

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('works.index') }}"
                       class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Guardar obra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

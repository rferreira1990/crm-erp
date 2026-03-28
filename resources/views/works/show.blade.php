<div class="rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">Alterar estado</h2>
    </div>

    <div class="p-6">
        @if (!empty($availableStatuses))
            <form method="POST" action="{{ route('works.change-status', $work) }}">
                @csrf
                @method('PATCH')

                <div class="mb-4">
                    <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Novo estado</label>
                    <select id="status"
                            name="status"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required>
                        <option value="">Seleciona</option>
                        @foreach ($availableStatuses as $status => $label)
                            <option value="{{ $status }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="status_notes" class="mb-1 block text-sm font-medium text-gray-700">Observações</label>
                    <textarea id="status_notes"
                              name="status_notes"
                              rows="3"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Atualizar estado
                </button>
            </form>
        @else
            <p class="text-sm text-gray-500">Não existem transições disponíveis para o estado atual.</p>
        @endif
    </div>
</div>

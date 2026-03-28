@php
    $selectedTeam = old('team', isset($work) && $work ? $work->team->pluck('id')->map(fn ($id) => (string) $id)->all() : []);
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold mb-2">Existem erros no formulário:</p>
        <ul class="list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div>
        <label for="customer_id" class="mb-1 block text-sm font-medium text-gray-700">Cliente *</label>
        <select id="customer_id"
                name="customer_id"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Seleciona um cliente</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}"
                    @selected((string) old('customer_id', $work->customer_id ?? '') === (string) $customer->id)>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nome da obra *</label>
        <input type="text"
               id="name"
               name="name"
               value="{{ old('name', $work->name ?? '') }}"
               required
               maxlength="255"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="work_type" class="mb-1 block text-sm font-medium text-gray-700">Tipo de obra</label>
        <input type="text"
               id="work_type"
               name="work_type"
               value="{{ old('work_type', $work->work_type ?? '') }}"
               maxlength="100"
               placeholder="Ex.: Instalação elétrica, manutenção, avaria..."
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="technical_manager_id" class="mb-1 block text-sm font-medium text-gray-700">Responsável técnico</label>
        <select id="technical_manager_id"
                name="technical_manager_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Seleciona um utilizador</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}"
                    @selected((string) old('technical_manager_id', $work->technical_manager_id ?? '') === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="location" class="mb-1 block text-sm font-medium text-gray-700">Local / morada</label>
        <input type="text"
               id="location"
               name="location"
               value="{{ old('location', $work->location ?? '') }}"
               maxlength="255"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="postal_code" class="mb-1 block text-sm font-medium text-gray-700">Código postal</label>
        <input type="text"
               id="postal_code"
               name="postal_code"
               value="{{ old('postal_code', $work->postal_code ?? '') }}"
               maxlength="20"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="city" class="mb-1 block text-sm font-medium text-gray-700">Cidade</label>
        <input type="text"
               id="city"
               name="city"
               value="{{ old('city', $work->city ?? '') }}"
               maxlength="120"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="budget_id" class="mb-1 block text-sm font-medium text-gray-700">Orçamento associado</label>
        <input type="number"
               id="budget_id"
               name="budget_id"
               value="{{ old('budget_id', $work->budget_id ?? '') }}"
               min="1"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <p class="mt-1 text-xs text-gray-500">Para já podes indicar o ID do orçamento. Depois ligamos isto numa seleção melhor.</p>
    </div>

    <div>
        <label for="start_date_planned" class="mb-1 block text-sm font-medium text-gray-700">Início previsto</label>
        <input type="date"
               id="start_date_planned"
               name="start_date_planned"
               value="{{ old('start_date_planned', isset($work->start_date_planned) ? $work->start_date_planned->format('Y-m-d') : '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="end_date_planned" class="mb-1 block text-sm font-medium text-gray-700">Fim previsto</label>
        <input type="date"
               id="end_date_planned"
               name="end_date_planned"
               value="{{ old('end_date_planned', isset($work->end_date_planned) ? $work->end_date_planned->format('Y-m-d') : '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="start_date_actual" class="mb-1 block text-sm font-medium text-gray-700">Início real</label>
        <input type="date"
               id="start_date_actual"
               name="start_date_actual"
               value="{{ old('start_date_actual', isset($work->start_date_actual) ? $work->start_date_actual->format('Y-m-d') : '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="end_date_actual" class="mb-1 block text-sm font-medium text-gray-700">Fim real</label>
        <input type="date"
               id="end_date_actual"
               name="end_date_actual"
               value="{{ old('end_date_actual', isset($work->end_date_actual) ? $work->end_date_actual->format('Y-m-d') : '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div class="md:col-span-2">
        <label for="team" class="mb-1 block text-sm font-medium text-gray-700">Equipa associada</label>
        <select id="team"
                name="team[]"
                multiple
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(in_array((string) $user->id, $selectedTeam, true))>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Podes selecionar vários utilizadores carregando Ctrl ou Cmd.</p>
    </div>

    <div class="md:col-span-2">
        <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Descrição</label>
        <textarea id="description"
                  name="description"
                  rows="4"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $work->description ?? '') }}</textarea>
    </div>

    <div class="md:col-span-2">
        <label for="internal_notes" class="mb-1 block text-sm font-medium text-gray-700">Notas internas</label>
        <textarea id="internal_notes"
                  name="internal_notes"
                  rows="4"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('internal_notes', $work->internal_notes ?? '') }}</textarea>
    </div>
</div>

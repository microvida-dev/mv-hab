<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="housing_unit_id" value="Habitação" />
        <select id="housing_unit_id" name="housing_unit_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            <option value="">Selecione</option>
            @foreach ($housingUnits as $housingUnit)
                <option value="{{ $housingUnit->id }}" @selected(old('housing_unit_id', $maintenanceRequest->housing_unit_id ?? '') == $housingUnit->id)>
                    {{ $housingUnit->code }} - {{ $housingUnit->address }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('housing_unit_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="citizen_id" value="Munícipe" />
        <select id="citizen_id" name="citizen_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="">Sem munícipe associado</option>
            @foreach ($citizens as $citizen)
                <option value="{{ $citizen->id }}" @selected(old('citizen_id', $maintenanceRequest->citizen_id ?? '') == $citizen->id)>
                    {{ $citizen->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('citizen_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="title" value="Título" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $maintenanceRequest->title ?? '')" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="priority" value="Prioridade" />
        <select id="priority" name="priority" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            @foreach ($priorities as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', isset($maintenanceRequest) ? $maintenanceRequest->priority->value : '') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" value="Estado" />
        <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', isset($maintenanceRequest) ? $maintenanceRequest->status->value : '') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="reported_at" value="Reportado em" />
        <x-text-input id="reported_at" name="reported_at" type="datetime-local" class="mt-1 block w-full" :value="old('reported_at', isset($maintenanceRequest) ? $maintenanceRequest->reported_at?->format('Y-m-d\TH:i') : '')" required />
        <x-input-error :messages="$errors->get('reported_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="resolved_at" value="Resolvido em" />
        <x-text-input id="resolved_at" name="resolved_at" type="datetime-local" class="mt-1 block w-full" :value="old('resolved_at', isset($maintenanceRequest) ? $maintenanceRequest->resolved_at?->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('resolved_at')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" value="Descrição" />
        <textarea id="description" name="description" rows="5" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>{{ old('description', $maintenanceRequest->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>

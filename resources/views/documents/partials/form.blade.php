<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="name" value="Nome do documento" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $document->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="citizen_id" value="Munícipe" />
        <select id="citizen_id" name="citizen_id" class="mv-input mt-1">
            <option value="">Sem associação</option>
            @foreach ($citizens as $citizen)
                <option value="{{ $citizen->id }}" @selected(old('citizen_id', $document->citizen_id ?? '') == $citizen->id)>
                    {{ $citizen->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('citizen_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="housing_application_id" value="Candidatura" />
        <select id="housing_application_id" name="housing_application_id" class="mv-input mt-1">
            <option value="">Sem associação</option>
            @foreach ($applications as $applicationOption)
                <option value="{{ $applicationOption->id }}" @selected(old('housing_application_id', $document->housing_application_id ?? '') == $applicationOption->id)>
                    #{{ $applicationOption->id }} - {{ $applicationOption->citizen->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('housing_application_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="contract_id" value="Contrato" />
        <select id="contract_id" name="contract_id" class="mv-input mt-1">
            <option value="">Sem associação</option>
            @foreach ($contracts as $contractOption)
                <option value="{{ $contractOption->id }}" @selected(old('contract_id', $document->contract_id ?? '') == $contractOption->id)>
                    #{{ $contractOption->id }} - {{ $contractOption->citizen->name }} - {{ $contractOption->housingUnit->code }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('contract_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="file" value="Ficheiro" />
        <input id="file" name="file" type="file" class="mv-input mt-1 block w-full">
        <x-input-error :messages="$errors->get('file')" class="mt-2" />

        @isset($document)
            <p class="mt-2 text-sm text-ink-500">Atual: {{ $document->path }}</p>
        @endisset
    </div>
</div>

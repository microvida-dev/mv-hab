<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="code" value="Código" />
        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $housingUnit->code ?? '')" required />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="typology" value="Tipologia" />
        <x-text-input id="typology" name="typology" type="text" class="mt-1 block w-full" :value="old('typology', $housingUnit->typology ?? '')" required />
        <x-input-error :messages="$errors->get('typology')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="address" value="Morada" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $housingUnit->address ?? '')" required />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="bedrooms" value="Quartos" />
        <x-text-input id="bedrooms" name="bedrooms" type="number" min="0" class="mt-1 block w-full" :value="old('bedrooms', $housingUnit->bedrooms ?? 0)" required />
        <x-input-error :messages="$errors->get('bedrooms')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="monthly_rent" value="Renda mensal" />
        <x-text-input id="monthly_rent" name="monthly_rent" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('monthly_rent', $housingUnit->monthly_rent ?? '0.00')" required />
        <x-input-error :messages="$errors->get('monthly_rent')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="status" value="Estado" />
        <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', isset($housingUnit) ? $housingUnit->status->value : '') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>

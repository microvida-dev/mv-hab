@php($contractStatuses = \App\Enums\ContractStatus::options())

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="citizen_id" value="Munícipe" />
        <select id="citizen_id" name="citizen_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            <option value="">Selecione</option>
            @foreach ($citizens as $citizen)
                <option value="{{ $citizen->id }}" @selected(old('citizen_id', $contract->citizen_id ?? '') == $citizen->id)>
                    {{ $citizen->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('citizen_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="housing_unit_id" value="Habitação" />
        <select id="housing_unit_id" name="housing_unit_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            <option value="">Selecione</option>
            @foreach ($housingUnits as $housingUnit)
                <option value="{{ $housingUnit->id }}" @selected(old('housing_unit_id', $contract->housing_unit_id ?? '') == $housingUnit->id)>
                    {{ $housingUnit->code }} - {{ $housingUnit->address }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('housing_unit_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="start_date" value="Data de início" />
        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', isset($contract) ? $contract->start_date?->format('Y-m-d') : '')" required />
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_date" value="Data de fim" />
        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', isset($contract) ? $contract->end_date?->format('Y-m-d') : '')" />
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="monthly_rent" value="Renda mensal" />
        <x-text-input id="monthly_rent" name="monthly_rent" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('monthly_rent', $contract->monthly_rent ?? '0.00')" required />
        <x-input-error :messages="$errors->get('monthly_rent')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" value="Estado" />
        <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            @foreach ($contractStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', isset($contract) ? $contract->status->value : '') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>

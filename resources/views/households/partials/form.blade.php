<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <x-input-label for="citizen_id" value="Munícipe responsável" />
        <select id="citizen_id" name="citizen_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
            <option value="">Selecione</option>
            @foreach ($citizens as $citizen)
                <option value="{{ $citizen->id }}" @selected(old('citizen_id', $household->citizen_id ?? '') == $citizen->id)>
                    {{ $citizen->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('citizen_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="Nome do agregado" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $household->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="members_count" value="Número de membros" />
        <x-text-input id="members_count" name="members_count" type="number" min="1" class="mt-1 block w-full" :value="old('members_count', $household->members_count ?? 1)" required />
        <x-input-error :messages="$errors->get('members_count')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="monthly_income" value="Rendimento mensal" />
        <x-text-input id="monthly_income" name="monthly_income" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('monthly_income', $household->monthly_income ?? '0.00')" required />
        <x-input-error :messages="$errors->get('monthly_income')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notas" />
        <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes', $household->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>

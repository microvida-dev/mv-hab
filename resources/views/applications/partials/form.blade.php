<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="citizen_id" value="Munícipe" />
        <select id="citizen_id" name="citizen_id" class="mv-input mt-1" required>
            <option value="">Selecione</option>
            @foreach ($citizens as $citizen)
                <option value="{{ $citizen->id }}" @selected(old('citizen_id', $application->citizen_id ?? '') == $citizen->id)>
                    {{ $citizen->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('citizen_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="household_id" value="Agregado familiar" />
        <select id="household_id" name="household_id" class="mv-input mt-1">
            <option value="">Sem agregado associado</option>
            @foreach ($households as $household)
                <option value="{{ $household->id }}" @selected(old('household_id', $application->household_id ?? '') == $household->id)>
                    {{ $household->name }} - {{ $household->citizen->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('household_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" value="Estado" />
        <select id="status" name="status" class="mv-input mt-1" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', isset($application) ? $application->status->value : '') == $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="priority_score" value="Pontuação de prioridade" />
        <x-text-input id="priority_score" name="priority_score" type="number" min="0" class="mt-1 block w-full" :value="old('priority_score', $application->priority_score ?? 0)" required />
        <x-input-error :messages="$errors->get('priority_score')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="submitted_at" value="Data de submissão" />
        <x-text-input id="submitted_at" name="submitted_at" type="datetime-local" class="mt-1 block w-full" :value="old('submitted_at', isset($application) ? $application->submitted_at?->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('submitted_at')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notas" />
        <textarea id="notes" name="notes" rows="4" class="mv-input mt-1">{{ old('notes', $application->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>

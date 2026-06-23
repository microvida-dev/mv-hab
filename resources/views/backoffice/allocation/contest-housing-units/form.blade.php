@php($item = $contestHousingUnit)
<div class="grid gap-4 md:grid-cols-2">
    <label class="text-sm font-medium text-ink-700">Programa
        <select name="program_id" class="mt-1 w-full rounded-md border-ink-200">
            <option value="">Selecione</option>
            @foreach($programs as $program)<option value="{{ $program->id }}" @selected((string) old('program_id', $item?->program_id) === (string) $program->id)>{{ $program->name }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-medium text-ink-700">Concurso
        <select name="contest_id" class="mt-1 w-full rounded-md border-ink-200">
            <option value="">Selecione</option>
            @foreach($contests as $contest)<option value="{{ $contest->id }}" @selected((string) old('contest_id', $item?->contest_id) === (string) $contest->id)>{{ $contest->title }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-medium text-ink-700 md:col-span-2">Habitação
        <select name="housing_unit_id" class="mt-1 w-full rounded-md border-ink-200">
            @foreach($housingUnits as $housingUnit)<option value="{{ $housingUnit->id }}" @selected((string) old('housing_unit_id', $item?->housing_unit_id) === (string) $housingUnit->id)>{{ $housingUnit->code }} · {{ $housingUnit->address }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-medium text-ink-700">Tipologia<input name="typology" value="{{ old('typology', $item?->typology) }}" class="mt-1 w-full rounded-md border-ink-200"></label>
    <label class="text-sm font-medium text-ink-700">Quartos<input type="number" name="bedrooms" value="{{ old('bedrooms', $item?->bedrooms) }}" class="mt-1 w-full rounded-md border-ink-200"></label>
    <label class="text-sm font-medium text-ink-700">Ocupação mínima<input type="number" name="min_occupants" value="{{ old('min_occupants', $item?->min_occupants) }}" class="mt-1 w-full rounded-md border-ink-200"></label>
    <label class="text-sm font-medium text-ink-700">Ocupação máxima<input type="number" name="max_occupants" value="{{ old('max_occupants', $item?->max_occupants) }}" class="mt-1 w-full rounded-md border-ink-200"></label>
    <label class="flex items-center gap-2 text-sm font-medium text-ink-700"><input type="hidden" name="accessible" value="0"><input type="checkbox" name="accessible" value="1" @checked(old('accessible', $item?->accessible))> Acessível</label>
    <label class="text-sm font-medium text-ink-700">Renda mensal<input type="number" step="0.01" name="monthly_rent" value="{{ old('monthly_rent', $item?->monthly_rent) }}" class="mt-1 w-full rounded-md border-ink-200"></label>
    <label class="text-sm font-medium text-ink-700 md:col-span-2">Notas<textarea name="notes" class="mt-1 w-full rounded-md border-ink-200">{{ old('notes', $item?->notes) }}</textarea></label>
</div>

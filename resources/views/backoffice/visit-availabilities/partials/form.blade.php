<form method="POST" action="{{ $action }}" class="mv-surface space-y-5 p-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="title" value="Título" />
        <input id="title" name="title" value="{{ old('title', $availability?->title) }}" class="mv-input mt-1 w-full text-sm" required>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <x-input-label for="starts_at" value="Início" />
            <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $availability?->starts_at?->format('Y-m-d\\TH:i')) }}" class="mv-input mt-1 w-full text-sm" required>
        </div>
        <div>
            <x-input-label for="ends_at" value="Fim" />
            <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $availability?->ends_at?->format('Y-m-d\\TH:i')) }}" class="mv-input mt-1 w-full text-sm" required>
        </div>
        <div>
            <x-input-label for="slot_duration_minutes" value="Duração do slot" />
            <input id="slot_duration_minutes" type="number" min="5" name="slot_duration_minutes" value="{{ old('slot_duration_minutes', $availability?->slot_duration_minutes ?? 30) }}" class="mv-input mt-1 w-full text-sm" required>
        </div>
        <div>
            <x-input-label for="capacity_per_slot" value="Capacidade por slot" />
            <input id="capacity_per_slot" type="number" min="1" name="capacity_per_slot" value="{{ old('capacity_per_slot', $availability?->capacity_per_slot ?? 1) }}" class="mv-input mt-1 w-full text-sm" required>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <select name="contest_id" class="mv-input text-sm">
            <option value="">Concurso</option>
            @foreach ($contests as $contest)
                <option value="{{ $contest->id }}" @selected(old('contest_id', $availability?->contest_id) == $contest->id)>{{ $contest->title }}</option>
            @endforeach
        </select>
        <select name="housing_unit_id" class="mv-input text-sm">
            <option value="">Habitação</option>
            @foreach ($housingUnits as $housingUnit)
                <option value="{{ $housingUnit->id }}" @selected(old('housing_unit_id', $availability?->housing_unit_id) == $housingUnit->id)>{{ $housingUnit->code }} · {{ $housingUnit->title }}</option>
            @endforeach
        </select>
        <select name="staff_user_id" class="mv-input text-sm">
            <option value="">Técnico</option>
            @foreach ($staffUsers as $staff)
                <option value="{{ $staff->id }}" @selected(old('staff_user_id', $availability?->staff_user_id) == $staff->id)>{{ $staff->name }}</option>
            @endforeach
        </select>
    </div>
    <textarea name="description" rows="4" class="mv-input w-full text-sm" placeholder="Descrição">{{ old('description', $availability?->description) }}</textarea>
    <label class="flex items-center gap-2 text-sm text-ink-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $availability?->is_active ?? true)) class="mv-checkbox">
        Ativa
    </label>
    <div class="flex justify-end gap-3">
        <a href="{{ route('backoffice.visit-availabilities.index') }}" class="mv-button-secondary">Cancelar</a>
        <button type="submit" class="mv-button-primary">Guardar</button>
    </div>
</form>

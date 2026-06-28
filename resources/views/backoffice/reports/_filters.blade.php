<form method="GET" class="grid gap-3 border-y border-ink-100 py-4 md:grid-cols-3 xl:grid-cols-6" aria-label="Filtros analíticos">
    <div><x-input-label for="date_from" value="Desde" /><x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="$filters['date_from'] ?? ''" /></div>
    <div><x-input-label for="date_to" value="Até" /><x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="$filters['date_to'] ?? ''" /></div>
    <div>
        <x-input-label for="program_id" value="Programa" />
        <select id="program_id" name="program_id" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
            <option value="">Todos</option>
            @foreach ($programs as $program)<option value="{{ $program->id }}" @selected(($filters['program_id'] ?? null) == $program->id)>{{ $program->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <x-input-label for="contest_id" value="Concurso" />
        <select id="contest_id" name="contest_id" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
            <option value="">Todos</option>
            @foreach ($contests as $contest)<option value="{{ $contest->id }}" @selected(($filters['contest_id'] ?? null) == $contest->id)>{{ $contest->title }}</option>@endforeach
        </select>
    </div>
    <div><x-input-label for="status" value="Estado" /><x-text-input id="status" name="status" class="mt-1 block w-full" :value="$filters['status'] ?? ''" /></div>
    <div><x-input-label for="location" value="Localização" /><x-text-input id="location" name="location" class="mt-1 block w-full" :value="$filters['location'] ?? ''" /></div>
    <div><x-input-label for="typology" value="Tipologia" /><x-text-input id="typology" name="typology" class="mt-1 block w-full" :value="$filters['typology'] ?? ''" /></div>
    <div><x-input-label for="parish" value="Freguesia" /><x-text-input id="parish" name="parish" class="mt-1 block w-full" :value="$filters['parish'] ?? ''" /></div>
    <div class="flex items-end gap-2"><button class="mv-button-primary" type="submit">Aplicar</button><a class="mv-button-secondary" href="{{ url()->current() }}">Limpar</a></div>
</form>

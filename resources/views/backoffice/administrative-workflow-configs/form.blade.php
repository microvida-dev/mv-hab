<input name="name" value="{{ old('name', $config?->name) }}" class="w-full rounded-md border-ink-300 text-sm" placeholder="Nome">
<div class="grid gap-3 sm:grid-cols-2">
    <select name="program_id" class="rounded-md border-ink-300 text-sm">
        <option value="">Programa</option>
        @foreach ($programs as $program)
            <option value="{{ $program->id }}" @selected((string) old('program_id', $config?->program_id) === (string) $program->id)>{{ $program->name }}</option>
        @endforeach
    </select>
    <select name="contest_id" class="rounded-md border-ink-300 text-sm">
        <option value="">Concurso</option>
        @foreach ($contests as $contest)
            <option value="{{ $contest->id }}" @selected((string) old('contest_id', $config?->contest_id) === (string) $contest->id)>{{ $contest->title }}</option>
        @endforeach
    </select>
</div>
<input type="number" min="1" max="120" name="default_correction_deadline_days" value="{{ old('default_correction_deadline_days', $config?->default_correction_deadline_days ?? 10) }}" class="w-full rounded-md border-ink-300 text-sm">
<div class="grid gap-3 sm:grid-cols-2">
    <label class="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $config?->is_active ?? true)) class="rounded border-ink-300">Ativa</label>
    <label class="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="requires_decision_approval" value="1" @checked(old('requires_decision_approval', $config?->requires_decision_approval ?? false)) class="rounded border-ink-300">Exige aprovação de decisão</label>
</div>
<button class="mv-button-primary">Guardar</button>

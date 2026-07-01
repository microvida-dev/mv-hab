<label class="grid gap-1 text-sm">
    <span class="font-medium text-ink-700">Nome</span>
    <input name="name" value="{{ old('name', $team?->name) }}" class="rounded-2xl border-ink-200" required>
</label>
<label class="grid gap-1 text-sm">
    <span class="font-medium text-ink-700">Descrição</span>
    <textarea name="description" rows="3" class="rounded-2xl border-ink-200">{{ old('description', $team?->description) }}</textarea>
</label>
<div class="grid gap-4 md:grid-cols-2">
    <label class="grid gap-1 text-sm">
        <span class="font-medium text-ink-700">Estado</span>
        <select name="status" class="rounded-2xl border-ink-200" required>
            <option value="active" @selected(old('status', $team?->status ?? 'active') === 'active')>Ativa</option>
            <option value="inactive" @selected(old('status', $team?->status) === 'inactive')>Inativa</option>
        </select>
    </label>
    <label class="grid gap-1 text-sm">
        <span class="font-medium text-ink-700">Responsável</span>
        <select name="manager_user_id" class="rounded-2xl border-ink-200">
            <option value="">Sem responsável</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('manager_user_id', $team?->manager_user_id) === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </label>
</div>
<label class="grid gap-1 text-sm">
    <span class="font-medium text-ink-700">Escopos funcionais</span>
    <textarea name="functional_scopes" rows="3" class="rounded-2xl border-ink-200">{{ old('functional_scopes', collect($team?->functional_scopes ?? [])->join(', ')) }}</textarea>
</label>
<label class="grid gap-1 text-sm">
    <span class="font-medium text-ink-700">Justificação</span>
    <textarea name="justification" rows="3" class="rounded-2xl border-ink-200" required>{{ old('justification') }}</textarea>
</label>

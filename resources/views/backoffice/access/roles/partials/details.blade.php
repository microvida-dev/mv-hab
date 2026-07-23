<x-mv.section title="Identificação do perfil" padding="p-5">
    <div class="grid gap-4 md:grid-cols-2">
        <label class="grid gap-1 text-sm">
            <span class="font-medium text-ink-700">Designação</span>
            <input name="label" value="{{ old('label', $role?->label) }}" class="mv-input" maxlength="120" required>
            <x-input-error :messages="$errors->get('label')" />
        </label>
        <label class="grid gap-1 text-sm md:col-span-2">
            <span class="font-medium text-ink-700">Descrição</span>
            <textarea name="description" rows="3" class="mv-input" maxlength="2000">{{ old('description', $role?->description) }}</textarea>
            <x-input-error :messages="$errors->get('description')" />
        </label>
    </div>
</x-mv.section>

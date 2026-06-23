@php($editing = isset($ruleSet))

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nome" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $ruleSet->name ?? '')" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>
    <div>
        <x-input-label for="status" value="Estado" />
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-ink-200">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', isset($ruleSet) ? $ruleSet->status->value : 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>
    <div>
        <x-input-label for="program_id" value="Programa" />
        <select id="program_id" name="program_id" class="mt-1 block w-full rounded-md border-ink-200">
            <option value="">Selecionar</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected((string) old('program_id', $ruleSet->program_id ?? '') === (string) $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('program_id')" />
    </div>
    <div>
        <x-input-label for="contest_id" value="Concurso específico (opcional)" />
        <select id="contest_id" name="contest_id" class="mt-1 block w-full rounded-md border-ink-200">
            <option value="">Aplicar ao programa</option>
            @foreach ($contests as $contest)
                <option value="{{ $contest->id }}" @selected((string) old('contest_id', $ruleSet->contest_id ?? '') === (string) $contest->id)>{{ $contest->title }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('contest_id')" />
    </div>
    <div>
        <x-input-label for="starts_at" value="Início de vigência" />
        <x-text-input id="starts_at" type="datetime-local" name="starts_at" class="mt-1 block w-full" :value="old('starts_at', isset($ruleSet) ? $ruleSet->starts_at?->format('Y-m-d\\TH:i') : '')" />
    </div>
    <div>
        <x-input-label for="ends_at" value="Fim de vigência" />
        <x-text-input id="ends_at" type="datetime-local" name="ends_at" class="mt-1 block w-full" :value="old('ends_at', isset($ruleSet) ? $ruleSet->ends_at?->format('Y-m-d\\TH:i') : '')" />
        <x-input-error class="mt-2" :messages="$errors->get('ends_at')" />
    </div>
    <div class="lg:col-span-2">
        <x-input-label for="description" value="Descrição" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-ink-200">{{ old('description', $ruleSet->description ?? '') }}</textarea>
    </div>
    <label class="flex items-center gap-3 text-sm text-ink-700 lg:col-span-2">
        <input type="checkbox" name="is_default" value="1" class="rounded border-ink-300 text-civic-700" @checked(old('is_default', $ruleSet->is_default ?? false))>
        Usar como conjunto padrão do programa
    </label>
</div>

<div class="mt-6 flex gap-3">
    <button class="mv-button-primary" type="submit">{{ $editing ? 'Guardar alterações' : 'Criar conjunto' }}</button>
    <a href="{{ route('backoffice.eligibility.rule-sets.index') }}" class="mv-button-secondary">Cancelar</a>
</div>

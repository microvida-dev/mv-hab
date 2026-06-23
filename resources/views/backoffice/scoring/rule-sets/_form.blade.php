@php($ruleSet = $ruleSet ?? new \App\Models\ScoringRuleSet)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="program_id" value="Programa" />
            <select id="program_id" name="program_id" class="mt-1 block w-full rounded-md border-ink-200">
                <option value="">Sem programa</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" @selected((int) old('program_id', $ruleSet->program_id) === $program->id)>{{ $program->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="contest_id" value="Concurso" />
            <select id="contest_id" name="contest_id" class="mt-1 block w-full rounded-md border-ink-200">
                <option value="">Sem concurso específico</option>
                @foreach ($contests as $contest)
                    <option value="{{ $contest->id }}" @selected((int) old('contest_id', $ruleSet->contest_id) === $contest->id)>{{ $contest->title }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('contest_id')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="name" value="Nome" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $ruleSet->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Descrição" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-ink-200">{{ old('description', $ruleSet->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid gap-5 md:grid-cols-3">
        <div>
            <x-input-label for="status" value="Estado" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-ink-200">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $ruleSet->status?->value ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="starts_at" value="Início" />
            <x-text-input id="starts_at" type="datetime-local" name="starts_at" class="mt-1 block w-full" :value="old('starts_at', $ruleSet->starts_at?->format('Y-m-d\\TH:i'))" />
            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="ends_at" value="Fim" />
            <x-text-input id="ends_at" type="datetime-local" name="ends_at" class="mt-1 block w-full" :value="old('ends_at', $ruleSet->ends_at?->format('Y-m-d\\TH:i'))" />
            <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
        </div>
    </div>

    <label class="flex items-center gap-3 text-sm text-ink-700">
        <input type="hidden" name="is_default" value="0">
        <input type="checkbox" name="is_default" value="1" class="rounded border-ink-300 text-civic-700" @checked(old('is_default', $ruleSet->is_default))>
        Matriz base por defeito
    </label>

    <div class="flex flex-wrap gap-3">
        <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white hover:bg-civic-800">Guardar</button>
        <a href="{{ route('backoffice.scoring.rule-sets.index') }}" class="rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 hover:bg-ink-50">Cancelar</a>
    </div>
</form>

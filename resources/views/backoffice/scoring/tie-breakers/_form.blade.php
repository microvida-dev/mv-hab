@php($rule = $rule ?? new \App\Models\TieBreakerRule)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @isset($method)
        @method($method)
    @endisset
    <div class="grid gap-5 md:grid-cols-2">
        <div><x-input-label for="code" value="Código" /><x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $rule->code)" required /></div>
        <div><x-input-label for="name" value="Nome" /><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $rule->name)" required /></div>
    </div>
    <div><x-input-label for="description" value="Descrição" /><textarea id="description" name="description" rows="3" class="mv-input mt-1 block w-full">{{ old('description', $rule->description) }}</textarea></div>
    <div class="grid gap-5 md:grid-cols-4">
        <div>
            <x-input-label for="target" value="Alvo" />
            <select id="target" name="target" class="mv-input mt-1 block w-full">
                @foreach ($targets as $value => $label)
                    <option value="{{ $value }}" @selected(old('target', $rule->target) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="direction" value="Direção" />
            <select id="direction" name="direction" class="mv-input mt-1 block w-full">
                @foreach ($directions as $value => $label)
                    <option value="{{ $value }}" @selected(old('direction', $rule->direction?->value ?? 'asc') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><x-input-label for="priority_order" value="Prioridade" /><x-text-input id="priority_order" name="priority_order" type="number" class="mt-1 block w-full" :value="old('priority_order', $rule->priority_order ?? 0)" /></div>
        <label class="mt-7 flex items-center gap-2 text-sm text-ink-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="mv-checkbox" @checked(old('is_active', $rule->exists ? $rule->is_active : true))> Ativa</label>
    </div>
    <div class="flex flex-wrap gap-3"><button class="mv-button-primary">Guardar</button><a href="{{ route('backoffice.scoring.tie-breakers.index', $ruleSet) }}" class="mv-button-secondary">Cancelar</a></div>
</form>

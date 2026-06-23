@php($rule = $rule ?? new \App\Models\ScoringRule)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="label" value="Etiqueta" />
            <x-text-input id="label" name="label" class="mt-1 block w-full" :value="old('label', $rule->label)" required />
        </div>
        <div>
            <x-input-label for="operator" value="Operador" />
            <select id="operator" name="operator" class="mt-1 block w-full rounded-md border-ink-200">
                <option value="">Usar operador do critério</option>
                @foreach ($operators as $value => $label)
                    <option value="{{ $value }}" @selected(old('operator', $rule->operator?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Descrição" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-ink-200">{{ old('description', $rule->description) }}</textarea>
    </div>

    <div class="grid gap-5 md:grid-cols-5">
        <div><x-input-label for="value" value="Valor" /><x-text-input id="value" name="value" class="mt-1 block w-full" :value="old('value', is_array($rule->value) ? json_encode($rule->value, JSON_UNESCAPED_UNICODE) : $rule->value)" /></div>
        <div><x-input-label for="minimum_value" value="Mínimo" /><x-text-input id="minimum_value" name="minimum_value" type="number" step="0.01" class="mt-1 block w-full" :value="old('minimum_value', $rule->minimum_value)" /></div>
        <div><x-input-label for="maximum_value" value="Máximo" /><x-text-input id="maximum_value" name="maximum_value" type="number" step="0.01" class="mt-1 block w-full" :value="old('maximum_value', $rule->maximum_value)" /></div>
        <div><x-input-label for="points" value="Pontos" /><x-text-input id="points" name="points" type="number" step="0.01" class="mt-1 block w-full" :value="old('points', $rule->points ?? 0)" required /></div>
        <div><x-input-label for="weight" value="Peso" /><x-text-input id="weight" name="weight" type="number" step="0.001" class="mt-1 block w-full" :value="old('weight', $rule->weight ?? 1)" /></div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div><x-input-label for="sort_order" value="Ordem" /><x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full" :value="old('sort_order', $rule->sort_order ?? 0)" /></div>
        <label class="mt-7 flex items-center gap-2 text-sm text-ink-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded border-ink-300 text-civic-700" @checked(old('is_active', $rule->exists ? $rule->is_active : true))> Ativa</label>
    </div>

    <div class="flex flex-wrap gap-3">
        <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white hover:bg-civic-800">Guardar</button>
        <a href="{{ route('backoffice.scoring.rules.index', $criterion) }}" class="rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 hover:bg-ink-50">Cancelar</a>
    </div>
</form>

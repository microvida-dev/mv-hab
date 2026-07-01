@php($criterion = $criterion ?? new \App\Models\ScoringCriterion)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <x-input-label for="code" value="Código" />
            <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $criterion->code)" required />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $criterion->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Descrição" />
        <textarea id="description" name="description" rows="3" class="mv-input mt-1 block w-full">{{ old('description', $criterion->description) }}</textarea>
    </div>

    <div class="grid gap-5 md:grid-cols-4">
        <div>
            <x-input-label for="category" value="Categoria" />
            <select id="category" name="category" class="mv-input mt-1 block w-full">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $criterion->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="target" value="Alvo" />
            <select id="target" name="target" class="mv-input mt-1 block w-full">
                @foreach ($targets as $value => $label)
                    <option value="{{ $value }}" @selected(old('target', $criterion->target) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="calculation_type" value="Tipo" />
            <select id="calculation_type" name="calculation_type" class="mv-input mt-1 block w-full">
                @foreach ($calculationTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('calculation_type', $criterion->calculation_type?->value ?? 'fixed_points') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="operator" value="Operador" />
            <select id="operator" name="operator" class="mv-input mt-1 block w-full">
                <option value="">Sem operador</option>
                @foreach ($operators as $value => $label)
                    <option value="{{ $value }}" @selected(old('operator', $criterion->operator?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-5">
        <div><x-input-label for="minimum_value" value="Mínimo" /><x-text-input id="minimum_value" name="minimum_value" type="number" step="0.01" class="mt-1 block w-full" :value="old('minimum_value', $criterion->minimum_value)" /></div>
        <div><x-input-label for="maximum_value" value="Máximo" /><x-text-input id="maximum_value" name="maximum_value" type="number" step="0.01" class="mt-1 block w-full" :value="old('maximum_value', $criterion->maximum_value)" /></div>
        <div><x-input-label for="points" value="Pontos" /><x-text-input id="points" name="points" type="number" step="0.01" class="mt-1 block w-full" :value="old('points', $criterion->points)" /></div>
        <div><x-input-label for="max_points" value="Máx. pontos" /><x-text-input id="max_points" name="max_points" type="number" step="0.01" class="mt-1 block w-full" :value="old('max_points', $criterion->max_points)" /></div>
        <div><x-input-label for="weight" value="Peso" /><x-text-input id="weight" name="weight" type="number" step="0.001" class="mt-1 block w-full" :value="old('weight', $criterion->weight ?? 1)" /></div>
    </div>

    <div>
        <x-input-label for="expected_value" value="Valor esperado (JSON ou valor simples)" />
        <x-text-input id="expected_value" name="expected_value" class="mt-1 block w-full" :value="old('expected_value', is_array($criterion->expected_value) ? json_encode($criterion->expected_value, JSON_UNESCAPED_UNICODE) : $criterion->expected_value)" />
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <label class="flex items-center gap-2 text-sm text-ink-700"><input type="hidden" name="requires_manual_review" value="0"><input type="checkbox" name="requires_manual_review" value="1" class="mv-checkbox" @checked(old('requires_manual_review', $criterion->requires_manual_review))> Manual</label>
        <label class="flex items-center gap-2 text-sm text-ink-700"><input type="hidden" name="is_exclusionary" value="0"><input type="checkbox" name="is_exclusionary" value="1" class="mv-checkbox" @checked(old('is_exclusionary', $criterion->is_exclusionary))> Exclusionary</label>
        <label class="flex items-center gap-2 text-sm text-ink-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="mv-checkbox" @checked(old('is_active', $criterion->exists ? $criterion->is_active : true))> Ativo</label>
        <div><x-input-label for="sort_order" value="Ordem" /><x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full" :value="old('sort_order', $criterion->sort_order ?? 0)" /></div>
    </div>

    <div class="grid gap-5 md:grid-cols-3">
        <div><x-input-label for="success_message" value="Mensagem sucesso" /><textarea id="success_message" name="success_message" rows="3" class="mv-input mt-1 block w-full">{{ old('success_message', $criterion->success_message) }}</textarea></div>
        <div><x-input-label for="failure_message" value="Mensagem falha" /><textarea id="failure_message" name="failure_message" rows="3" class="mv-input mt-1 block w-full">{{ old('failure_message', $criterion->failure_message) }}</textarea></div>
        <div><x-input-label for="review_message" value="Mensagem manual" /><textarea id="review_message" name="review_message" rows="3" class="mv-input mt-1 block w-full">{{ old('review_message', $criterion->review_message) }}</textarea></div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button class="mv-button-primary">Guardar</button>
        <a href="{{ route('backoffice.scoring.criteria.index', $ruleSet) }}" class="mv-button-secondary">Cancelar</a>
    </div>
</form>

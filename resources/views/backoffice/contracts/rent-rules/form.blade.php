@php($editing = isset($rule))
<div class="grid gap-5 md:grid-cols-2">
    <div><x-input-label for="rent_rule_set_id" value="Conjunto" /><select id="rent_rule_set_id" name="rent_rule_set_id" class="mt-1 w-full rounded-md border-ink-200">@foreach($ruleSets as $ruleSet)<option value="{{ $ruleSet->id }}" @selected((string) old('rent_rule_set_id', $rule->rent_rule_set_id ?? '') === (string) $ruleSet->id)>{{ $ruleSet->name }}</option>@endforeach</select></div>
    <div><x-input-label for="name" value="Nome" /><x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $rule->name ?? '')" required /></div>
    <div><x-input-label for="rule_type" value="Tipo" /><x-text-input id="rule_type" name="rule_type" class="mt-1 w-full" :value="old('rule_type', $rule->rule_type ?? 'income_bracket')" required /></div>
    <div><x-input-label for="operator" value="Operador" /><x-text-input id="operator" name="operator" class="mt-1 w-full" :value="old('operator', $rule->operator ?? 'between')" /></div>
    <div><x-input-label for="minimum_value" value="Valor mínimo" /><x-text-input id="minimum_value" name="minimum_value" type="number" step="0.01" class="mt-1 w-full" :value="old('minimum_value', $rule->minimum_value ?? '')" /></div>
    <div><x-input-label for="maximum_value" value="Valor máximo" /><x-text-input id="maximum_value" name="maximum_value" type="number" step="0.01" class="mt-1 w-full" :value="old('maximum_value', $rule->maximum_value ?? '')" /></div>
    <div><x-input-label for="fixed_amount" value="Valor fixo" /><x-text-input id="fixed_amount" name="fixed_amount" type="number" step="0.01" class="mt-1 w-full" :value="old('fixed_amount', $rule->fixed_amount ?? '')" /></div>
    <div><x-input-label for="percentage" value="Percentagem" /><x-text-input id="percentage" name="percentage" type="number" step="0.01" class="mt-1 w-full" :value="old('percentage', $rule->percentage ?? '')" /></div>
    <div><x-input-label for="priority_order" value="Prioridade" /><x-text-input id="priority_order" name="priority_order" type="number" class="mt-1 w-full" :value="old('priority_order', $rule->priority_order ?? 0)" /></div>
    <label class="flex items-center gap-3 text-sm text-ink-700"><input type="checkbox" name="is_active" value="1" class="rounded border-ink-300 text-civic-700" @checked(old('is_active', $rule->is_active ?? true))> Ativa</label>
    <div class="md:col-span-2"><x-input-label for="description" value="Descrição" /><textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-md border-ink-200">{{ old('description', $rule->description ?? '') }}</textarea></div>
</div>
<div class="mt-6 flex gap-3"><button class="mv-button-primary">{{ $editing ? 'Guardar' : 'Criar' }}</button><a href="{{ route('backoffice.contracts.rent-rules.index') }}" class="mv-button-secondary">Cancelar</a></div>

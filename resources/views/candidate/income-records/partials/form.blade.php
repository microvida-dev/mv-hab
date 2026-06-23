@php
    $incomeRecord = $incomeRecord ?? null;
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <x-input-label for="household_member_id" value="Membro do agregado *" />
        <select id="household_member_id" name="household_member_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
            <option value="">Selecione</option>
            @foreach ($household->members as $member)
                <option value="{{ $member->id }}" @selected((int) old('household_member_id', $incomeRecord?->household_member_id) === $member->id) @disabled($member->has_no_income)>
                    {{ $member->full_name }}{{ $member->has_no_income ? ' — sem rendimentos' : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('household_member_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="income_source_id" value="Fonte de rendimento *" />
        <select id="income_source_id" name="income_source_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
            <option value="">Selecione</option>
            @foreach ($incomeSources as $source)
                <option value="{{ $source->id }}" @selected((int) old('income_source_id', $incomeRecord?->income_source_id) === $source->id)>{{ $source->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('income_source_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" value="Descrição" />
        <x-text-input id="description" name="description" class="mt-1 block w-full" :value="old('description', $incomeRecord?->description)" />
    </div>

    <div>
        <x-input-label for="monthly_amount" value="Valor mensal" />
        <x-text-input id="monthly_amount" name="monthly_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('monthly_amount', $incomeRecord?->monthly_amount)" />
        <x-input-error :messages="$errors->get('monthly_amount')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="annual_amount" value="Valor anual" />
        <x-text-input id="annual_amount" name="annual_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('annual_amount', $incomeRecord?->annual_amount)" />
        <x-input-error :messages="$errors->get('annual_amount')" class="mt-2" />
        <p class="mt-1 text-xs text-ink-500">Se preencher apenas um valor, o outro será calculado automaticamente.</p>
    </div>

    <div>
        <x-input-label for="reference_year" value="Ano de referência" />
        <x-text-input id="reference_year" name="reference_year" type="number" min="2000" max="2100" class="mt-1 block w-full" :value="old('reference_year', $incomeRecord?->reference_year ?? now()->year)" />
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <x-input-label for="starts_at" value="Início" />
            <x-text-input id="starts_at" name="starts_at" type="date" class="mt-1 block w-full" :value="old('starts_at', $incomeRecord?->starts_at?->toDateString())" />
        </div>
        <div>
            <x-input-label for="ends_at" value="Fim" />
            <x-text-input id="ends_at" name="ends_at" type="date" class="mt-1 block w-full" :value="old('ends_at', $incomeRecord?->ends_at?->toDateString())" />
        </div>
    </div>

    <div class="md:col-span-2 grid gap-3 sm:grid-cols-2">
        <label class="flex items-center gap-3 rounded-md border border-ink-100 p-3 text-sm text-ink-700">
            <input type="checkbox" name="is_current" value="1" class="rounded border-gray-300 text-civic-700" @checked(old('is_current', $incomeRecord?->is_current ?? true))>
            Rendimento atual
        </label>
        <label class="flex items-center gap-3 rounded-md border border-ink-100 p-3 text-sm text-ink-700">
            <input type="checkbox" name="is_taxable" value="1" class="rounded border-gray-300 text-civic-700" @checked(old('is_taxable', $incomeRecord?->is_taxable ?? true))>
            Sujeito a tributação
        </label>
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Observações" />
        <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $incomeRecord?->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>

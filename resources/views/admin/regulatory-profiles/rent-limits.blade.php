@php
    $rows = old('rows');
    if ($rows === null) {
        $rows = $manifest?->rows?->map(fn ($row) => [
            'typology' => $row->typology,
            'minimum_rent' => $row->minimum_rent,
            'maximum_rent' => $row->maximum_rent,
            'source_row_reference' => $row->source_row_reference,
        ])->all() ?? [];
    }
    $rows = array_pad($rows, max(6, count($rows)), []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Configuração regulamentar</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Tabela oficial de limites de renda</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $regulatoryProfile->name }} · {{ $municipality->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            @if ($errors->any())<x-mv.alert tone="danger">{{ $errors->first() }}</x-mv.alert>@endif

            @if ($ruleSets->isEmpty())
                <section class="mv-surface p-6">
                    <x-mv.alert tone="warning">Ainda não existe um conjunto de regras de renda associado a este perfil e ao programa do Município.</x-mv.alert>
                    <div class="mt-5 flex gap-3">
                        <a href="{{ route('backoffice.contracts.rent-rule-sets.create') }}" class="mv-button-primary">Criar regras de renda</a>
                        <a href="{{ route('admin.regulatory-profiles.show', $regulatoryProfile) }}" class="mv-button-secondary">Voltar ao perfil</a>
                    </div>
                </section>
            @else
                <section class="mv-surface p-6">
                    <form method="GET" action="{{ route('admin.regulatory-profiles.rent-limits.edit', $regulatoryProfile) }}" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                        <div><x-input-label for="rent_rule_set_selector" value="Conjunto de regras de renda" /><select id="rent_rule_set_selector" name="rent_rule_set_id" class="mv-input mt-1 block w-full">@foreach($ruleSets as $ruleSet)<option value="{{ $ruleSet->id }}" @selected($selectedRuleSet?->id === $ruleSet->id)>{{ $ruleSet->program?->name }} · {{ $ruleSet->name }}</option>@endforeach</select></div>
                        <button class="mv-button-secondary">Carregar</button>
                    </form>
                </section>

                <form method="POST" action="{{ route('admin.regulatory-profiles.rent-limits.update', $regulatoryProfile) }}" class="mv-surface p-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="rent_rule_set_id" value="{{ $selectedRuleSet?->id }}">
                    <x-mv.alert class="mb-6">A versão da fonte será fixada como <strong>{{ $regulatoryProfile->source_version ?: 'não definida' }}</strong>. Introduza apenas valores transcritos da tabela oficial aplicável.</x-mv.alert>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div class="lg:col-span-2"><x-input-label for="source_document" value="Documento-fonte" /><textarea id="source_document" name="source_document" rows="3" class="mv-input mt-1 block w-full" required>{{ old('source_document', $manifest->source_document ?? '') }}</textarea></div>
                        <div><x-input-label for="source_reference" value="Referência oficial" /><x-text-input id="source_reference" name="source_reference" class="mt-1 w-full" :value="old('source_reference', $manifest->source_reference ?? '')" required /></div>
                        <div><x-input-label value="Versão da fonte" /><div class="mv-input mt-1 flex min-h-11 items-center bg-ink-50 font-semibold text-ink-800">{{ $regulatoryProfile->source_version ?: 'Defina no perfil antes de guardar' }}</div></div>
                        <div><x-input-label for="effective_from" value="Vigente desde" /><x-text-input id="effective_from" name="effective_from" type="date" class="mt-1 w-full" :value="old('effective_from', $manifest?->effective_from?->format('Y-m-d') ?? $regulatoryProfile->effective_from?->format('Y-m-d'))" required /></div>
                        <div><x-input-label for="effective_until" value="Vigente até" /><x-text-input id="effective_until" name="effective_until" type="date" class="mt-1 w-full" :value="old('effective_until', $manifest?->effective_until?->format('Y-m-d') ?? $regulatoryProfile->effective_until?->format('Y-m-d'))" /></div>
                    </div>

                    <div class="mt-8">
                        <div class="flex items-end justify-between gap-4"><div><h2 class="text-lg font-semibold text-ink-900">Limites por tipologia</h2><p class="mt-1 text-sm text-ink-500">Deixe linhas não utilizadas completamente vazias.</p></div><x-mv.badge>{{ strtoupper($municipality->code) }}</x-mv.badge></div>
                        <div class="mt-4 overflow-x-auto rounded-2xl border border-ink-100">
                            <table class="min-w-full text-sm">
                                <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Tipologia</th><th class="px-4 py-3">Renda mínima</th><th class="px-4 py-3">Renda máxima</th><th class="px-4 py-3">Linha/fonte</th></tr></thead>
                                <tbody class="divide-y divide-ink-100">
                                    @foreach($rows as $index => $row)
                                        <tr>
                                            <td class="p-3"><x-text-input name="rows[{{ $index }}][typology]" class="w-28" :value="$row['typology'] ?? ''" placeholder="T1" /></td>
                                            <td class="p-3"><x-text-input name="rows[{{ $index }}][minimum_rent]" type="number" step="0.01" class="w-36" :value="$row['minimum_rent'] ?? ''" /></td>
                                            <td class="p-3"><x-text-input name="rows[{{ $index }}][maximum_rent]" type="number" step="0.01" class="w-36" :value="$row['maximum_rent'] ?? ''" /></td>
                                            <td class="p-3"><x-text-input name="rows[{{ $index }}][source_row_reference]" class="min-w-48" :value="$row['source_row_reference'] ?? ''" placeholder="Quadro / linha / artigo" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3"><a href="{{ route('admin.regulatory-profiles.show', $regulatoryProfile) }}" class="mv-button-secondary">Voltar</a><button class="mv-button-primary">Validar e guardar tabela</button></div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
